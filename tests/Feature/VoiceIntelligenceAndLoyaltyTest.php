<?php

use App\Events\SupervisorBarged;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SentimentEvaluationService;
use App\Services\TenantSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

test('Webhook transcript detects Spanish and triggers multilingual voice hot-swapping', function () {
    Http::fake();

    $tenant = Tenant::factory()->create(['secret_key' => 'test-secret']);
    TenantScope::setTenantId($tenant->id);
    config(['services.telephony.provider' => 'vapi']);

    // Call endpoint with Spanish transcription event
    $response = $this->postJson('/api/webhooks/call-events', [
        'event' => 'transcript',
        'call_id' => 'multilingual-call-123',
        'transcript' => 'Hola, buenos dias. Tengo un gran problema con mi calefaccion.',
        'tenant_id' => $tenant->id,
    ], [
        'X-Signature' => hash_hmac('sha256', json_encode([
            'event' => 'transcript',
            'call_id' => 'multilingual-call-123',
            'transcript' => 'Hola, buenos dias. Tengo un gran problema con mi calefaccion.',
            'tenant_id' => $tenant->id,
        ]), 'test-secret'),
    ]);

    $response->assertOk();

    // Verify Vapi PATCH call was made to update language and voice
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call/multilingual-call-123'
            && $request->method() === 'PATCH'
            && $request['assistantOverrides']['transcriber']['language'] === 'es'
            && $request['assistantOverrides']['voice']['model'] === 'eleven_multilingual_v2';
    });

    // Check language cache
    expect(Cache::get('call-language-swapped:multilingual-call-123'))->toBe('es');
});

test('SentimentEvaluationService computes sentiment index and triggers auto-escalation when threshold breached', function () {
    Http::fake();
    Event::fake([SupervisorBarged::class]);

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);
    config(['services.telephony.provider' => 'vapi']);

    $service = app(SentimentEvaluationService::class);

    // Turn 1: Negative sentiment (no emergency keyword)
    // Phi will be negative (-1.0) because it contains negative words: "broken terrible worst"
    // Urgency = 1.0, distress decay increases by 0.15
    $score1 = $service->evaluateTurn('sentiment-call-999', 'My HVAC system is completely broken and terrible. This is the worst service.', $tenant->id);

    // Score: Phi = -1.0, distress accumulator = 0.15. Score = -1.0 - 0.15 = -1.15
    expect($score1)->toBeLessThan(0.0);

    // Turn 2: Emergency turn ("gas leak")
    // Contains emergency keyword "gas leak" (urgency multiplier = 2.5)
    // Phi will be negative (-1.0) because of negative words: "leak smell emergency"
    // distress accumulator increases by 0.15 to 0.30
    $score2 = $service->evaluateTurn('sentiment-call-999', 'I smell a gas leak and this is a major emergency help!', $tenant->id);

    // Average weighted sentiment: ((-1.0 * 1.0) + (-1.0 * 2.5)) / 2 = -1.75
    // distress decay = 0.30
    // Total index: -1.75 - 0.30 = -2.05 (threshold breached < -0.65)
    expect($score2)->toBeLessThan(-0.65);

    // Assert Vapi/Retell barge API was triggered
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call/sentiment-call-999/barge'
            && $request['mode'] === 'barge';
    });

    // Assert Reverb SupervisorBarged broadcasting event was fired
    Event::assertDispatched(SupervisorBarged::class, function ($event) {
        return $event->callId === 'sentiment-call-999'
            && $event->mode === 'barge'
            && $event->supervisorName === 'Auto-Escalation System';
    });
});

test('Tenants can register specialized trade terminology and inject them inside call initialization payloads', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // 1. Post keywords via settings endpoint
    $response = $this->actingAs($user)->postJson('/api/settings/specialized-keywords', [
        'keywords' => ['Trane', 'Lennox', 'Rheem', 'compressor blowout'],
    ]);

    $response->assertOk()
        ->assertJsonFragment(['success' => true]);

    $tenant->refresh();
    expect($tenant->getSetting('specialized_keywords'))->toContain('Trane', 'Lennox', 'Rheem');

    // 2. Generate assistant overrides and assert keywords are injected
    $settingsService = app(TenantSettingsService::class);
    $payload = $settingsService->generateAssistantPayload($tenant);

    expect($payload['assistantOverrides']['transcriber']['keywords'])
        ->toContain('Trane', 'Lennox', 'Rheem', 'compressor blowout');
});
