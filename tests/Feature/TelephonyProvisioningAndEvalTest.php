<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CallEvaluationService;
use App\Services\TelephonyProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('TelephonyProvisioningService provisions phone number for Vapi provider', function () {
    Http::fake([
        'https://api.vapi.ai/phone-number' => Http::response([
            'id' => 'vapi-phone-id-123',
            'number' => '+12065550199',
        ], 200),
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $tenant = Tenant::factory()->create();
    $tenant->settings = ['voice_assistant_id' => 'assist-456'];
    $tenant->save();

    $service = app(TelephonyProvisioningService::class);
    $result = $service->provisionPhoneNumber($tenant, '206');

    expect($result)->toBe([
        'phone_number' => '+12065550199',
        'phone_number_id' => 'vapi-phone-id-123',
    ]);

    $tenant->refresh();
    expect($tenant->getSetting('telephony_phone_number_id'))->toBe('vapi-phone-id-123')
        ->and($tenant->getSetting('telephony_phone_number'))->toBe('+12065550199');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.vapi.ai/phone-number' &&
            $request['areaCode'] === '206' &&
            $request['assistantId'] === 'assist-456';
    });
});

test('TelephonyProvisioningService provisions phone number for Retell provider', function () {
    Http::fake([
        'https://api.retellai.com/buy-phone-number' => Http::response([
            'phone_number_id' => 'retell-phone-id-789',
            'phone_number' => '+16045550123',
        ], 200),
    ]);

    config(['services.telephony.provider' => 'retell']);

    $tenant = Tenant::factory()->create();
    $tenant->settings = ['voice_assistant_id' => 'assist-abc'];
    $tenant->save();

    $service = app(TelephonyProvisioningService::class);
    $result = $service->provisionPhoneNumber($tenant, '604');

    expect($result)->toBe([
        'phone_number' => '+16045550123',
        'phone_number_id' => 'retell-phone-id-789',
    ]);

    $tenant->refresh();
    expect($tenant->getSetting('telephony_phone_number_id'))->toBe('retell-phone-id-789')
        ->and($tenant->getSetting('telephony_phone_number'))->toBe('+16045550123');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.retellai.com/buy-phone-number' &&
            $request['area_code'] === 604 &&
            $request['assistant_id'] === 'assist-abc';
    });
});

test('CallEvaluationService calculates Theta_eval based on API scorecards', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-eval-test-1',
        'status' => 'ended',
        'customer_phone' => '+15550100',
    ]);

    Http::fake([
        'https://api.vapi.ai/call/*' => Http::response([
            'analysis' => [
                'intentAccomplished' => true,
                'evaluations' => [
                    ['name' => 'collected_caller_name', 'passed' => true],
                    ['name' => 'remained_polite', 'passed' => true],
                    ['name' => 'did_not_hallucinate', 'passed' => false],
                    ['name' => 'offered_options', 'passed' => true],
                ],
            ],
        ], 200),
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $service = app(CallEvaluationService::class);
    $score = $service->evaluateCall($callLog);

    // E_violations = 1, P_checks = 4, intent = 1
    // (1 - 1/4) * 1 = 0.75
    expect($score)->toBe(0.75);

    $callLog->refresh();
    expect($callLog->conversational_eval_score)->toBe(0.75);
});

test('CallEvaluationService applies fallback evaluation if API request fails', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-eval-test-2',
        'status' => 'ended',
        'customer_phone' => '+15550200',
        'transcript' => 'The agent did not collect my email and was impolite.',
    ]);

    Http::fake([
        'https://api.vapi.ai/call/*' => Http::response([], 500),
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $service = app(CallEvaluationService::class);
    $score = $service->evaluateCall($callLog);

    // Fallbacks checks: Checks = 3.
    // transcript has "impolite" (violation 1), "did not collect" (violation 2)
    // E_violations = 2, P_checks = 3, intent = 0 (due to "did not collect" or fallback scanning of fail/error/etc. Wait, intent fallback scans for fail/could not/error, transcript has none, so intent = 1)
    // (1 - 2/3) * 1 = 0.3333...
    expect($score)->toBeGreaterThan(0.33)->toBeLessThan(0.34);

    $callLog->refresh();
    expect($callLog->conversational_eval_score)->toBeGreaterThan(0.33);
});

test('WebCallController refresh token proxy endpoint succeeds for authenticated user', function () {
    $_ENV['VAPI_PUBLIC_KEY'] = 'dummy-vapi-public-key';
    $_SERVER['VAPI_PUBLIC_KEY'] = 'dummy-vapi-public-key';
    putenv('VAPI_PUBLIC_KEY=dummy-vapi-public-key');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    config(['services.telephony.provider' => 'vapi']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/web-calls/refresh-token');

    $response->assertOk()
        ->assertJsonStructure([
            'provider',
            'access_token',
            'expires_in',
        ])
        ->assertJson([
            'provider' => 'vapi',
            'access_token' => 'dummy-vapi-public-key',
        ]);
});

test('WebCallController refresh token proxy endpoint rejects unauthenticated requests', function () {
    $response = $this->postJson('/api/web-calls/refresh-token');

    $response->assertStatus(401);
});
