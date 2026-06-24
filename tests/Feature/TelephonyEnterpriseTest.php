<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('middleware RestrictToTelephonyIps allowlists static provider IP and blocks others', function () {
    $tenant = Tenant::factory()->create();

    // Send request from random IP (e.g. 192.168.1.1) -> blocks with 403
    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-ip-test',
                'customer_phone_number' => '+15551112222',
            ],
        ]);

    $response->assertStatus(403);

    // Send request from allowlisted IP (100.20.5.228) -> passes IP middleware
    $response2 = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-ip-test-2',
                'customer_phone_number' => '+15551112222',
            ],
        ]);

    // Bypasses IP restriction middleware (signature header missing, returns 401, not 403)
    expect($response2->status())->not->toBe(403);
});

test('webhook endpoint validates Custom Credentials / bearer tokens', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'vapi-secret-key-123']);

    // Call without credentials -> 401
    $response = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-auth-test',
                'customer_phone_number' => '+15551112222',
            ],
        ]);
    $response->assertStatus(401);

    // Call with Bearer Token matching secret key -> 200
    $response2 = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->withToken('vapi-secret-key-123')
        ->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-auth-test-2',
                'customer_phone_number' => '+15551112222',
            ],
        ]);
    $response2->assertStatus(200);

    // Call with custom header X-Vapi-Secret matching secret key -> 200
    $response3 = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->withHeaders(['X-Vapi-Secret' => 'vapi-secret-key-123'])
        ->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-auth-test-3',
                'customer_phone_number' => '+15551112222',
            ],
        ]);
    $response3->assertStatus(200);
});

test('dispatch webhook routes to voicemail fallback when technician is unavailable', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);

    // No technician is configured. So dispatch will fail.
    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15550009999',
        'service_type' => 'hvac',
        'requested_time' => '2026-06-23 10:00:00',
    ];

    $response = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->postJson('/api/webhooks/dispatch', $payload);

    // Should return 422 with forward_to_voicemail action
    $response->assertStatus(422)
        ->assertJson([
            'status' => 'forward_to_voicemail',
            'action' => 'transfer',
            'destination' => '+18005550199',
        ]);
});

test('call completion webhook calculates CQS and parses voicemail', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null, 'plan' => 'Trial']);

    // Seed call log
    TenantScope::setTenantId($tenant->id);
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-cqs-test',
        'status' => 'ongoing',
        'customer_phone' => '+15551112222',
    ]);

    // End call with endReason, monitor latency, transcription confidence
    $payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'call-cqs-test',
            'customer_phone_number' => '+15551112222',
            'duration_seconds' => 120,
            'endReason' => 'customer-hung-up',
            'disconnectionSource' => 'user',
            'monitor' => [
                'latency' => [
                    'average' => 150, // Delta = 150
                ],
            ],
            'analysis' => [
                'transcriptionConfidence' => 0.90, // Theta = 0.90
            ],
            'toolCalls' => [
                // 1 successful tool call, 0 errors. Epsilon = 1.0
                [
                    'id' => 'tc-1',
                    'function' => [
                        'name' => 'check_inventory',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->withServerVariables(['REMOTE_ADDR' => '100.20.5.228'])
        ->postJson('/api/webhooks/call-events/'.$tenant->id, $payload);

    $response->assertStatus(200);

    $callLog->refresh();

    // Check mapped fields
    expect($callLog->call_end_reason)->toBe('user_hung_up');
    expect($callLog->disconnection_source)->toBe('user');
    expect($callLog->latency)->toBe(150);
    expect($callLog->transcription_confidence)->toBe(0.90);
    expect($callLog->tool_success_rate)->toBe(1.0);

    // CQS calculation check (Trial weights: w1 = 0.3, w2 = 0.3, w3 = 0.4)
    // CQS = 0.3 * (1 - 150/1500) + 0.3 * 0.90 + 0.4 * 1.0
    //     = 0.3 * 0.9 + 0.27 + 0.4 = 0.27 + 0.27 + 0.4 = 0.94
    expect($callLog->call_quality_score)->toBe(0.94);
});
