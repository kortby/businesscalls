<?php

use App\Ai\Text;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgentTransferService;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    TenantScope::setTenantId(null);
    Text::$mockResponse = null;
});

test('AgentTransferService formats Retell handover payload correctly', function () {
    $service = new AgentTransferService;
    $payload = $service->formatRetellHandoverPayload(
        'child-agent-123',
        'Customer wants to proceed to payments.',
        ['customer_name' => 'Alice', 'amount_due' => 150]
    );

    expect($payload)->toBe([
        'action' => 'agent_transfer',
        'agent_id' => 'child-agent-123',
        'inherit_transcript' => true,
        'transcript_history' => 'Customer wants to proceed to payments.',
        'variables' => [
            'customer_name' => 'Alice',
            'amount_due' => 150,
        ],
    ]);
});

test('AgentTransferService formats Vapi handover payload correctly', function () {
    $service = new AgentTransferService;
    $payload = $service->formatVapiHandoverPayload(
        'child-agent-456',
        'Transferring to survey.',
        ['survey_id' => 'csat-v1']
    );

    expect($payload)->toBe([
        'destination' => [
            'type' => 'assistant',
            'assistantId' => 'child-agent-456',
        ],
        'assistant' => [
            'id' => 'child-agent-456',
            'variableOverrides' => [
                'survey_id' => 'csat-v1',
            ],
        ],
        'context' => [
            'transcript' => 'Transferring to survey.',
            'variables' => [
                'survey_id' => 'csat-v1',
            ],
        ],
    ]);
});

test('AgentTransferService calculateHandoverScore computes score and enforces boundaries', function () {
    $service = new AgentTransferService;

    // 1. Basic calculation: shared = 2, total = 4, delay = 500ms
    // Phi = (2/4) * (1 - 500/1000) = 0.5 * 0.5 = 0.25
    expect($service->calculateHandoverScore(2, 4, 500.0))->toEqual(0.25);

    // 2. Total count is 0
    expect($service->calculateHandoverScore(2, 0, 100.0))->toEqual(0.0);

    // 3. Delay exceeds 1000ms (so delay component is 0.0)
    expect($service->calculateHandoverScore(2, 4, 1500.0))->toEqual(0.0);

    // 4. Delay is negative (latency component maxes out at 1.0)
    expect($service->calculateHandoverScore(2, 4, -200.0))->toEqual(0.5);
});

test('AgentTransferService saveHandoverScore computes and saves score to CallLog under tenant scope', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-uuid-999',
        'status' => 'active',
        'customer_phone' => '+15551234567',
    ]);

    $service = new AgentTransferService;
    // shared = 3, total = 5, delay = 200ms -> score = (3/5) * (1 - 0.2) = 0.6 * 0.8 = 0.48
    $score = $service->saveHandoverScore($callLog, 3, 5, 200.0);

    expect($score)->toEqual(0.48);
    expect($callLog->fresh()->contextual_handover_match_index)->toEqual(0.48);
});

test('LanguageDetectionMiddleware processes transcript via AI prompt and triggers Vapi patches', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'secret_key' => null,
        'client_id' => null,
    ]);

    // Mock the AI prompt response to return 'es' (Spanish)
    Text::$mockResponse = 'es';
    config(['services.telephony.provider' => 'vapi']);

    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-vapi-es-777',
        'transcript' => 'Hola, me gustaria comprar un boleto.',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.vapi.ai/call/call-vapi-es-777')
            && $request->method() === 'PATCH'
            && $request['transcriber']['language'] === 'es'
            && $request['pronunciation_dictionary']['language'] === 'es';
    });
});

test('LanguageDetectionMiddleware processes transcript via AI prompt and triggers Retell patches', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'secret_key' => null,
        'client_id' => null,
    ]);

    // Mock the AI prompt response to return 'fr' (French)
    Text::$mockResponse = 'fr';
    config(['services.telephony.provider' => 'retell']);

    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-retell-fr-888',
        'transcript' => 'Bonjour, comment allez-vous?',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.retellai.com/v2/calls/call-retell-fr-888')
            && $request->method() === 'PATCH'
            && $request['transcriber']['language'] === 'fr';
    });
});

test('LanguageDetectionMiddleware does not patch if English is detected', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'secret_key' => null,
        'client_id' => null,
    ]);

    Text::$mockResponse = 'en';

    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-en-111',
        'transcript' => 'Hello, I want to book a plumber.',
    ];

    $this->postJson('/api/webhooks/dispatch', $payload);

    Http::assertNothingSent();
});

test('admin/onboarding-board renders correct milestones state', function () {
    $tenant = Tenant::factory()->create([
        'plan' => 'free',
        'settings' => [],
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // 1. Initial State: all false
    $response = $this->actingAs($user)->get(route('admin.onboarding-board'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/OnboardingBoard')
            ->where('subscriptionActive', false)
            ->where('mascotSkinActive', false)
            ->where('phoneProvisioned', false)
            ->where('allMilestonesPassed', false)
        );

    // 2. Fully Configured State: all true
    $tenant->plan = 'pro';
    $tenant->settings = [
        'mascot_skin' => 'victory_gold',
        'phone_number' => '+15557778888',
    ];
    $tenant->save();

    $response = $this->actingAs($user)->get(route('admin.onboarding-board'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/OnboardingBoard')
            ->where('subscriptionActive', true)
            ->where('mascotSkinActive', true)
            ->where('phoneProvisioned', true)
            ->where('allMilestonesPassed', true)
        );
});
