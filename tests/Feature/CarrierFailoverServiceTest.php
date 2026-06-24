<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\CarrierFailoverService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('carrier failover service patches vapi assistant and retries dial', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'settings' => [
            'voice_assistant_id' => 'vapi-test-assistant',
            'fallback_phone_number_id' => 'vapi-backup-phone-id',
            'fallback_phone_number' => '+15559998888',
        ],
    ]);
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call_failed_original',
        'customer_phone' => '+15551234567',
        'status' => 'ended',
        'call_end_reason' => 'dial_failed',
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $service = new CarrierFailoverService;
    $result = $service->orchestrateFailover($tenant, $callLog, []);

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('vapi')
        ->and($result['fallback_phone_id'])->toBe('vapi-backup-phone-id');

    // Assert Vapi assistant patch endpoint was called with correct fallback phone ID
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/assistant/vapi-test-assistant'
            && $request->method() === 'PATCH'
            && $request['phoneNumberId'] === 'vapi-backup-phone-id';
    });

    // Assert retry call was placed
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.vapi.ai/call'
            && $request->method() === 'POST'
            && $request['phoneNumberId'] === 'vapi-backup-phone-id'
            && $request['customer']['number'] === '+15551234567';
    });
});

test('carrier failover service patches retell assistant and retries dial', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'settings' => [
            'voice_assistant_id' => 'retell-test-assistant',
            'fallback_phone_number_id' => 'retell-backup-phone-id',
            'fallback_phone_number' => '+15559998888',
        ],
    ]);
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call_failed_original_retell',
        'customer_phone' => '+15551234567',
        'status' => 'ended',
        'call_end_reason' => 'dial_busy',
    ]);

    config(['services.telephony.provider' => 'retell']);

    $service = new CarrierFailoverService;
    $result = $service->orchestrateFailover($tenant, $callLog, []);

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('retell')
        ->and($result['fallback_phone_id'])->toBe('retell-backup-phone-id');

    // Assert Retell assistant update endpoint was called
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.retellai.com/v2/assistants/retell-test-assistant'
            && $request->method() === 'PATCH'
            && $request['phone_number_id'] === 'retell-backup-phone-id';
    });

    // Assert Retell retry call was created
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.retellai.com/v2/create-phone-call'
            && $request->method() === 'POST'
            && $request['from_number'] === '+15559998888'
            && $request['to_number'] === '+15551234567';
    });
});
