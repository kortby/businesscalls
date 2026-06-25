<?php

use App\Jobs\SyncCallToCrmJob;
use App\Models\CallLog;
use App\Models\CrmCredential;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('SyncCallToCrmJob uses tenant CrmCredential to push HubSpot and Salesforce calls', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'secret-t1']);
    TenantScope::setTenantId($tenant->id);

    $hubspotCred = CrmCredential::create([
        'tenant_id' => $tenant->id,
        'platform_name' => 'hubspot',
        'access_token' => 't1-hubspot-token',
        'refresh_token' => 't1-hubspot-refresh',
        'token_expires_at' => now()->addHour(),
        'settings_json' => ['is_active' => true],
    ]);

    $salesforceCred = CrmCredential::create([
        'tenant_id' => $tenant->id,
        'platform_name' => 'salesforce',
        'access_token' => 't1-sf-token',
        'refresh_token' => 't1-sf-refresh',
        'token_expires_at' => now()->addHour(),
        'settings_json' => [
            'is_active' => true,
            'instance_url' => 'https://t1-sf.salesforce.com',
        ],
    ]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'oauth-call-101',
        'status' => 'ended',
        'customer_phone' => '+15550000001',
        'duration' => 180,
        'summary' => json_encode([
            'caller_name' => 'Dave Miller',
            'sentiment' => 'Neutral',
            'summary' => 'Requested HVAC checkup.',
            'booking_outcome' => 'Scheduled',
        ]),
        'transcript' => 'Dave: Pls check HVAC. AI: Sure.',
    ]);

    Http::fake([
        'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response(['results' => [['id' => 'hs-c-001']]], 200),
        'https://api.hubapi.com/crm/v3/objects/calls' => Http::response(['id' => 'hs-call-001'], 200),
        'https://api.hubapi.com/crm/v4/objects/calls/*' => Http::response([], 200),
        'https://t1-sf.salesforce.com/services/data/v57.0/query?q=*' => Http::response(['totalSize' => 1, 'records' => [['Id' => 'sf-c-001']]], 200),
        'https://t1-sf.salesforce.com/services/data/v57.0/sobjects/Task' => Http::response(['id' => 'sf-t-001'], 200),
    ]);

    $job = new SyncCallToCrmJob($callLog);
    $job->handle();

    expect($callLog->fresh()->crm_sync_status)->toBe('success')
        ->and($callLog->fresh()->crm_sync_latency)->toBeGreaterThanOrEqual(0);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/contacts/search' &&
               $request->hasHeader('Authorization', 'Bearer t1-hubspot-token');
    });

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 't1-sf.salesforce.com/services/data/v57.0/query') &&
               $request->hasHeader('Authorization', 'Bearer t1-sf-token');
    });
});

test('SyncCallToCrmJob refreshes expired HubSpot tokens and retries sync', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $hubspotCred = CrmCredential::create([
        'tenant_id' => $tenant->id,
        'platform_name' => 'hubspot',
        'access_token' => 'expired-token',
        'refresh_token' => 'valid-refresh-token',
        'token_expires_at' => now()->subMinutes(10), // expired!
        'settings_json' => [
            'is_active' => true,
            'client_id' => 'client-id-xyz',
            'client_secret' => 'client-secret-abc',
        ],
    ]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'oauth-call-102',
        'status' => 'ended',
        'customer_phone' => '+15550000002',
        'duration' => 90,
        'summary' => json_encode(['caller_name' => 'Alice']),
    ]);

    Http::fake([
        'https://api.hubapi.com/oauth/v1/token' => Http::response([
            'access_token' => 'new-refreshed-token',
            'expires_in' => 3600,
        ], 200),
        'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response(['results' => [['id' => 'hs-c-002']]], 200),
        'https://api.hubapi.com/crm/v3/objects/calls' => Http::response(['id' => 'hs-call-002'], 200),
        'https://api.hubapi.com/crm/v4/objects/calls/*' => Http::response([], 200),
    ]);

    $job = new SyncCallToCrmJob($callLog);
    $job->handle();

    $hubspotCred->refresh();
    expect($hubspotCred->access_token)->toBe('new-refreshed-token')
        ->and($hubspotCred->token_expires_at->isFuture())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.hubapi.com/crm/v3/objects/contacts/search' &&
               $request->hasHeader('Authorization', 'Bearer new-refreshed-token');
    });
});

test('SyncCallToCrmJob isolates tenant boundaries strictly', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Tenant 2 has active Salesforce creds
    TenantScope::setTenantId($tenant2->id);
    CrmCredential::create([
        'tenant_id' => $tenant2->id,
        'platform_name' => 'salesforce',
        'access_token' => 'tenant-2-token',
        'settings_json' => ['is_active' => true],
    ]);

    // Tenant 1 has a call log
    TenantScope::setTenantId($tenant1->id);
    $callLog = CallLog::create([
        'tenant_id' => $tenant1->id,
        'call_id' => 'oauth-call-103',
        'status' => 'ended',
        'customer_phone' => '+15550000003',
    ]);

    Http::fake();

    // Run job for Tenant 1 call log
    $job = new SyncCallToCrmJob($callLog);
    $job->handle();

    // Expect status skipped because Tenant 1 has no CRM credentials
    expect($callLog->fresh()->crm_sync_status)->toBe('skipped');
    Http::assertNothingSent();
});

test('mcp endpoint validates HMAC signatures and executes check_technician_gps tool', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'mcp-signature-secret']);
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Bob',
        'last_name' => 'Smith',
    ]);

    $payload = [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'check_technician_gps',
            'arguments' => [
                'employee_id' => $employee->id,
            ],
        ],
        'id' => 456,
    ];

    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, 'mcp-signature-secret');

    // 1. Send invalid signature -> 401
    $responseInvalid = $this->withHeaders([
        'Authorization' => 'Bearer mcp-signature-secret',
        'X-Vapi-Signature' => 'wrong-signature',
    ])->postJson(route('mcp.server'), $payload);

    $responseInvalid->assertStatus(401);

    // 2. Send valid signature -> 200 & coordinates
    $responseValid = $this->withHeaders([
        'Authorization' => 'Bearer mcp-signature-secret',
        'X-Vapi-Signature' => $signature,
    ])->postJson(route('mcp.server'), $payload);

    $responseValid->assertOk()
        ->assertJsonPath('result.latitude', 37.7749 + ($employee->id % 100) / 1000.0)
        ->assertJsonPath('result.longitude', -122.4194 + ($employee->id % 50) / 1000.0);
});
