<?php

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('webhooks gateway middleware uses Cache::touch to extend session TTL', function () {
    $tenant = Tenant::factory()->create([
        'secret_key' => 'secret-webhook-key',
    ]);

    // Seed cache with tenant session
    $cacheKey = 'tenant-session:'.$tenant->id;
    Cache::put($cacheKey, $tenant, 600);

    // Spy on Cache
    Cache::spy();

    // Perform payload request
    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551234567',
        'service_type' => 'plumbing',
        'requested_time' => '2026-06-23 12:00:00',
    ];

    $rawPayload = json_encode($payload);
    $signature = hash_hmac('sha256', $rawPayload, 'secret-webhook-key');

    $response = $this->call('POST', '/api/webhooks/dispatch', [], [], [], [
        'HTTP_X_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $rawPayload);

    // Assert that Cache::touch was called on the session key to extend the TTL
    Cache::shouldHaveReceived('touch')->with($cacheKey, 600);
});
