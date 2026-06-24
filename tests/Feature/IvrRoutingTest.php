<?php

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('ivr endpoint collects dtmf digits sequence in cache and resolves routes', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'ivr_detection_delay_ms' => 600,
            'ivr_routes' => [
                '1' => ['action' => 'transfer', 'agent_id' => 'agent_spanish'],
                '3' => ['action' => 'submenu', 'menu' => 'billing'],
                '21' => ['action' => 'transfer', 'agent_id' => 'agent_csat'],
            ],
        ],
    ]);

    // Send first digit: '2' (should result in collecting state since '2' is a prefix of '21' but not a complete route)
    $response = $this->postJson(route('webhook.ivr', ['tenant_id' => $tenant->id]), [
        'call_id' => 'call-ivr-1',
        'digit' => '2',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'digits_pressed' => '2',
            'action' => 'collecting',
            'detection_delay_ms' => 600,
        ]);

    // Send second digit: '1' (sequence '21' should match transfer to agent_csat)
    $response2 = $this->postJson(route('webhook.ivr', ['tenant_id' => $tenant->id]), [
        'call_id' => 'call-ivr-1',
        'digit' => '1',
    ]);

    $response2->assertOk()
        ->assertJson([
            'success' => true,
            'digits_pressed' => '21',
            'action' => 'transfer',
            'destination_agent_id' => 'agent_csat',
            'detection_delay_ms' => 600,
        ]);

    // Cache should be cleared after transfer action
    expect(Cache::has('ivr_sequence_call-ivr-1'))->toBeFalse();
});

test('webhook handles call_analyzed and evaluates mathematical csat scores', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);

    TenantScope::setTenantId($tenant->id);
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-csat-eval',
        'status' => 'ended',
        'customer_phone' => '+15551234567',
    ]);

    // CSAT ratings payload (scores: 5, 4, 3, K = 3)
    // Formula: ( (5 + 4 + 3) / (5 * 3) ) * 100 = (12 / 15) * 100 = 80.0%
    $payload = [
        'event' => 'call_analyzed',
        'call' => [
            'call_id' => 'call-csat-eval',
            'customer_phone_number' => '+15551234567',
            'transcript' => 'CSAT survey done.',
            'summary' => 'Finished survey',
            'survey_scores' => [5, 4, 3],
        ],
    ];

    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, $tenant->secret_key);

    $response = $this->postJson(
        '/api/webhooks/call-events/'.$tenant->id,
        $payload,
        ['X-Signature' => $signature]
    );

    $response->assertOk();

    $callLog->refresh();
    expect($callLog->csat_score)->toBe(80.0);
});
