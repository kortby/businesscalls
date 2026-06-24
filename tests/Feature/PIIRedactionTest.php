<?php

use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComplianceSanitizerService;

test('compliance service masks credit cards and SSNs correctly', function () {
    $service = new ComplianceSanitizerService;

    expect($service->sanitize('My card is 1234 5678 9012 3456.'))->toBe('My card is [REDACTED].')
        ->and($service->sanitize('My SSN is 999-88-7777.'))->toBe('My SSN is [REDACTED].')
        ->and($service->sanitize('Plain text without PII.'))->toBe('Plain text without PII.');
});

test('incoming webhook transcripts are auto-redacted', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'test-secret']);

    // Disregard IP filtering for unit test simplicity
    config(['services.telephony.provider' => 'vapi']);

    $response = $this->postJson("/api/webhooks/call-events/{$tenant->id}", [
        'event' => 'call_analyzed',
        'call' => [
            'call_id' => 'call-pii-123',
            'customer_phone' => '123-456-7890',
            'transcript' => 'Customer SSN: 123-45-6789 and Card: 4111-2222-3333-4444',
            'summary' => 'Customer shared credit card 4111-2222-3333-4444 details.',
        ],
    ], [
        'X-Vapi-Secret' => 'test-secret',
    ]);

    $response->assertOk();

    // Query DB bypassing TenantScope to assert auto-redaction
    TenantScope::setTenantId($tenant->id);
    $callLog = CallLog::where('call_id', 'call-pii-123')->first();

    expect($callLog)->not->toBeNull()
        ->and($callLog->transcript)->toBe('Customer SSN: [REDACTED] and Card: [REDACTED]')
        ->and($callLog->summary)->toBe('Customer shared credit card [REDACTED] details.');
});

test('supervisor can manually trigger call log redaction', function () {
    $tenant = Tenant::factory()->create();
    $supervisor = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-redact-me',
        'status' => 'ended',
        'customer_phone' => '123-456-7890',
        'transcript' => 'CC details: 4111 2222 3333 4444',
        'summary' => 'Customer shared 4111-2222-3333-4444.',
        'is_test_mode' => true,
    ]);

    $response = $this->actingAs($supervisor)->postJson("/api/call-logs/{$callLog->id}/redact");

    $response->assertOk()
        ->assertJsonFragment(['success' => true]);

    expect($callLog->fresh()->transcript)->toBe('CC details: [REDACTED]')
        ->and($callLog->fresh()->summary)->toBe('Customer shared [REDACTED].');

    // Assert Audit Log entry was recorded
    $auditLog = AuditLog::where('tenant_id', $tenant->id)
        ->where('action', 'manual_redaction')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($supervisor->id);
});

test('non-supervisors cannot manually redact call logs', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-redact-me-fail',
        'status' => 'ended',
        'customer_phone' => '123-456-7890',
        'transcript' => 'PII data: 123-45-6789',
        'is_test_mode' => true,
    ]);

    $response = $this->actingAs($user)->postJson("/api/call-logs/{$callLog->id}/redact");
    $response->assertStatus(403);
});
