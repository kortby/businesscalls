<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\AgentTransferService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('ivr controller handles single digit keypress webhook for vapi tool call', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'ivr_detection_delay_ms' => 450,
            'ivr_routes' => [
                '1' => ['action' => 'transfer', 'agent_id' => 'agent_emergency'],
                '2' => ['action' => 'submenu', 'menu' => 'billing'],
            ],
        ],
    ]);

    // Construct Vapi tool-call payload
    $payload = [
        'call_id' => 'call_12345',
        'message' => [
            'toolCall' => [
                'id' => 'tool_call_xyz',
                'function' => [
                    'arguments' => [
                        'digit' => '1',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson("/api/webhooks/ivr-keypress/{$tenant->id}", $payload);

    $response->assertStatus(200);

    // Assert structure matches Vapi tool response requirements
    $response->assertJson([
        'results' => [
            [
                'toolCallId' => 'tool_call_xyz',
                'result' => [
                    'success' => true,
                    'digits_pressed' => '1',
                    'action' => 'transfer',
                    'destination_agent_id' => 'agent_emergency',
                    'detection_delay_ms' => 450,
                ],
            ],
        ],
    ]);
});

test('ivr controller handles multi digit caching sequence', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'ivr_routes' => [
                '21' => ['action' => 'transfer', 'agent_id' => 'agent_support'],
            ],
        ],
    ]);

    $callId = 'call_multi_digit';

    // Press digit '2' first
    $payload1 = [
        'call_id' => $callId,
        'digit' => '2',
    ];

    $response1 = $this->postJson("/api/webhooks/ivr-keypress/{$tenant->id}", $payload1);
    $response1->assertStatus(200);
    $response1->assertJson([
        'success' => true,
        'digits_pressed' => '2',
        'action' => 'collecting', // waiting for potential matching '21'
    ]);

    // Press digit '1' second
    $payload2 = [
        'call_id' => $callId,
        'digit' => '1',
    ];

    $response2 = $this->postJson("/api/webhooks/ivr-keypress/{$tenant->id}", $payload2);
    $response2->assertStatus(200);
    $response2->assertJson([
        'success' => true,
        'digits_pressed' => '21',
        'action' => 'transfer',
        'destination_agent_id' => 'agent_support',
    ]);

    // Cache should be cleared after a successful transfer action
    expect(Cache::get("ivr_sequence_{$callId}"))->toBeNull();
});

test('agent transfer service compiles transfer to agent payload with context', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call_transfer_test',
        'customer_phone' => '+15558889999',
        'transcript' => 'User is calling to request urgent dispatch.',
        'status' => 'ongoing',
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $service = new AgentTransferService;
    $result = $service->transferToAgent($callLog, 'agent_spanish_rep', ['user_name' => 'John Doe']);

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('vapi')
        ->and($result['variables_transferred']['parent_call_id'])->toBe('call_transfer_test')
        ->and($result['variables_transferred']['transcript_history'])->toBe('User is calling to request urgent dispatch.')
        ->and($result['variables_transferred']['user_name'])->toBe('John Doe')
        ->and($result['payload']['assistantId'])->toBe('agent_spanish_rep');
});

test('agent transfer service compiles transfer to human payload', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call_human_transfer_test',
        'customer_phone' => '+15558889999',
        'status' => 'ongoing',
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $service = new AgentTransferService;
    $result = $service->transferToHuman($callLog, 'sip:operator@yourdomain.com', true);

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('vapi')
        ->and($result['payload']['destination']['type'])->toBe('sipUri')
        ->and($result['payload']['destination']['sipUri'])->toBe('sip:operator@yourdomain.com')
        ->and($result['payload']['destination']['transferMode'])->toBe('warm');
});
