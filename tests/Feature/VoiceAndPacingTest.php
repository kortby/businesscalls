<?php

use App\Jobs\SendTechnicianAlertJob;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\CustomVoice;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TenantSettingsService;
use App\Services\VoiceCloningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

afterEach(function () {
    SendTechnicianAlertJob::$shouldRunInTests = false;
});

test('voice cloning service invokes SDK simulation via multipart HTTP stream', function () {
    $service = new VoiceCloningService;

    Http::fake([
        'api.retellai.com/create-voice' => Http::response(['voice_id' => 'cloned_voice_777'], 200),
    ]);

    $file1 = UploadedFile::fake()->create('voice1.mp3', 200);
    $file2 = UploadedFile::fake()->create('voice2.mp3', 200);

    $voiceId = $service->cloneVoice('My Brand Voice', [$file1, $file2], 'elevenlabs', 'mock-retell-key');

    expect($voiceId)->toBe('cloned_voice_777');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.retellai.com/create-voice';
    });
});

test('voice cloning service patches vapi assistant with new voice ID', function () {
    $service = new VoiceCloningService;

    Http::fake([
        'api.vapi.ai/assistant/assistant_123' => Http::response(['success' => true], 200),
    ]);

    $success = $service->updateVapiVoice('assistant_123', 'elevenlabs_voice_xyz', 'mock-vapi-key');

    expect($success)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/assistant/assistant_123' &&
            $request['voice']['provider'] === 'elevenlabs' &&
            $request['voice']['voiceId'] === 'elevenlabs_voice_xyz';
    });
});

test('outbound alert job queries and applies custom voices', function () {
    SendTechnicianAlertJob::$shouldRunInTests = true;

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Create a custom voice for the tenant
    $customVoice = CustomVoice::factory()->create([
        'tenant_id' => $tenant->id,
        'provider_voice_id' => 'provider_cloned_voice_888',
        'status' => 'active',
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'notification_preference' => 'voice',
        'phone' => '+15550009999',
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'registered',
        'scheduled_start' => now()->addDay(),
    ]);

    config(['services.telephony.provider' => 'vapi']);
    Http::fake([
        'api.vapi.ai/call' => Http::response(['id' => 'vapi_call_555'], 200),
    ]);

    $job = new SendTechnicianAlertJob($booking);
    $job->handle();

    // Verify custom voice is passed in assistant overrides
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call' &&
            $request['assistantOverrides']['voice']['voiceId'] === 'provider_cloned_voice_888';
    });
});

test('speech pace alignment engine handles division by zero and pacing mismatches', function () {
    $tenant = Tenant::factory()->create([
        'secret_key' => 'test-secret-key-pacing',
    ]);
    TenantScope::setTenantId($tenant->id);

    $callId = 'pacing-call-id-999';

    // Fake Vapi patch endpoint
    config(['services.telephony.provider' => 'vapi']);
    Http::fake([
        "api.vapi.ai/call/{$callId}" => Http::response(['success' => true], 200),
    ]);

    // 1. Division by zero / zero duration boundary test (should fallback to target pace, no update)
    $payloadZeroDuration = [
        'type' => 'transcript',
        'transcript' => 'hello',
        'start_time' => 10.0,
        'end_time' => 10.0, // duration = 0
        'role' => 'user',
        'call' => [
            'id' => $callId,
        ],
    ];

    $response = $this->withHeaders([
        'X-Vapi-Secret' => 'test-secret-key-pacing',
    ])->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), $payloadZeroDuration);

    $response->assertStatus(200);
    Http::assertNotSent(function ($request) use ($callId) {
        return $request->url() === "https://api.vapi.ai/call/{$callId}";
    });

    // 2. Mismatch fast pacing: user speaking 10 words in 1 second (10 words/sec vs target 2 words/sec)
    // Congruence: 1 - |10 - 2|/10 = 1 - 8/10 = 0.2 < 0.7. Should trigger speed up of assistant.
    $payloadFastPacing = [
        'type' => 'transcript',
        'transcript' => 'one two three four five six seven eight nine ten',
        'start_time' => 0.0,
        'end_time' => 1.0,
        'role' => 'user',
        'call' => [
            'id' => $callId,
        ],
    ];

    // Clear Cache and fake HTTP again to inspect
    Cache::forget("speech-pace-adjusted:{$callId}");
    Http::fake([
        "api.vapi.ai/call/{$callId}" => Http::response(['success' => true], 200),
    ]);

    $response2 = $this->withHeaders([
        'X-Vapi-Secret' => 'test-secret-key-pacing',
    ])->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), $payloadFastPacing);

    $response2->assertStatus(200);

    Http::assertSent(function ($request) use ($callId) {
        return $request->url() === "https://api.vapi.ai/call/{$callId}" &&
            $request['assistantOverrides']['voice']['speed'] === 1.15;
    });

    // 3. Mismatch slow pacing: user speaking 1 word in 4 seconds (0.25 words/sec vs target 2 words/sec)
    // Congruence: 1 - |0.25 - 2|/2 = 1 - 1.75/2 = 0.125 < 0.7. Should trigger slow down of assistant.
    $payloadSlowPacing = [
        'type' => 'transcript',
        'transcript' => 'hello',
        'start_time' => 0.0,
        'end_time' => 4.0,
        'role' => 'user',
        'call' => [
            'id' => $callId,
        ],
    ];

    Cache::forget("speech-pace-adjusted:{$callId}");
    Http::fake([
        "api.vapi.ai/call/{$callId}" => Http::response(['success' => true], 200),
    ]);

    $response3 = $this->withHeaders([
        'X-Vapi-Secret' => 'test-secret-key-pacing',
    ])->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), $payloadSlowPacing);

    $response3->assertStatus(200);

    Http::assertSent(function ($request) use ($callId) {
        return $request->url() === "https://api.vapi.ai/call/{$callId}" &&
            $request['assistantOverrides']['voice']['speed'] === 0.85;
    });
});

test('calculate turn taking congruence index correctly computes and saves metric', function () {
    $tenant = Tenant::factory()->create();
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'turn-taking-call-123',
        'status' => 'ended',
        'customer_phone' => '123-456-7890',
    ]);

    // Actual pauses 500ms and 700ms (P_target = 600ms)
    // 1 - |500 - 600|/600 = 5/6 = 0.8333...
    // 1 - |700 - 600|/600 = 5/6 = 0.8333...
    // Expected avg congruence = 0.8333...
    $congruence = $callLog->calculateTurnTakingCongruence([500, 700]);

    expect($congruence)->toBeGreaterThan(0.83)
        ->and($congruence)->toBeLessThan(0.84)
        ->and($callLog->fresh()->turn_taking_congruence)->toBeGreaterThan(0.83);
});

test('tenant settings service includes custom speech timing overrides in vapi assistant overrides', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'startSpeakingPlan' => 500, // 500ms -> 0.5s waitSeconds
            'stopSpeakingPlan' => 0.4, // 0.4s voiceSeconds
        ],
    ]);

    $service = app(TenantSettingsService::class);
    $payload = $service->generateAssistantPayload($tenant);

    expect($payload['assistantOverrides']['startSpeakingPlan']['waitSeconds'])->toBe(0.5)
        ->and($payload['assistantOverrides']['stopSpeakingPlan']['voiceSeconds'])->toBe(0.4)
        ->and($payload['assistantOverrides']['stopSpeakingPlan']['numWords'])->toBe(0)
        ->and($payload['assistantOverrides']['stopSpeakingPlan']['backoffSeconds'])->toBe(1.0);
});
