<?php

use App\Events\CallAnalyzed;
use App\Events\CallEnded;
use App\Events\CallStarted;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('webhook endpoint rejects requests without signature', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);

    $response = $this->postJson(route('webhook.call-events', ['tenant_id' => $tenant->id]), [
        'event' => 'call_started',
        'call' => [
            'call_id' => 'call-123',
            'customer_phone_number' => '+15551234567',
        ],
    ]);

    $response->assertStatus(401);
});

test('webhook endpoint rejects requests with invalid signature', function () {
    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);

    $response = $this->postJson(
        route('webhook.call-events', ['tenant_id' => $tenant->id]),
        [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'call-123',
                'customer_phone_number' => '+15551234567',
            ],
        ],
        ['X-Signature' => 'bad-signature']
    );

    $response->assertStatus(401);
});

test('webhook endpoint handles call_started event with valid signature', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);
    $payload = [
        'event' => 'call_started',
        'call' => [
            'call_id' => 'call-123',
            'customer_phone_number' => '+15551234567',
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

    // Verify DB
    TenantScope::setTenantId($tenant->id);
    $this->assertDatabaseHas('call_logs', [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-123',
        'status' => 'ongoing',
        'customer_phone' => '+15551234567',
    ]);

    Event::assertDispatched(CallStarted::class, function ($event) use ($tenant) {
        return $event->tenantId === $tenant->id && $event->callLog->call_id === 'call-123';
    });
});

test('webhook endpoint handles call_ended event with valid signature', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);

    // Seed call log
    TenantScope::setTenantId($tenant->id);
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-123',
        'status' => 'ongoing',
        'customer_phone' => '+15551234567',
    ]);

    $payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'call-123',
            'customer_phone_number' => '+15551234567',
            'duration_seconds' => 180,
            'recording_url' => 'https://example.com/recording.mp3',
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

    // Verify DB
    $callLog->refresh();
    expect($callLog->status)->toBe('ended')
        ->and($callLog->duration)->toBe(180)
        ->and($callLog->recording_url)->toBe('https://example.com/recording.mp3');

    Event::assertDispatched(CallEnded::class, function ($event) use ($tenant) {
        return $event->tenantId === $tenant->id && $event->callLog->call_id === 'call-123';
    });
});

test('webhook endpoint handles call_analyzed event with valid signature', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['secret_key' => 'my-secret-key']);

    // Seed call log
    TenantScope::setTenantId($tenant->id);
    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-123',
        'status' => 'ended',
        'customer_phone' => '+15551234567',
    ]);

    $payload = [
        'event' => 'call_analyzed',
        'call' => [
            'call_id' => 'call-123',
            'customer_phone_number' => '+15551234567',
            'transcript' => 'I need plumbing service.',
            'summary' => 'Requested plumbing assistance',
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

    // Verify DB
    $callLog->refresh();
    expect($callLog->transcript)->toBe('I need plumbing service.')
        ->and($callLog->summary)->toBe('Requested plumbing assistance');

    Event::assertDispatched(CallAnalyzed::class, function ($event) use ($tenant) {
        return $event->tenantId === $tenant->id && $event->callLog->call_id === 'call-123';
    });
});
