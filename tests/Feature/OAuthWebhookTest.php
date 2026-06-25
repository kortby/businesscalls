<?php

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantOAuthToken;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('oauth endpoint exchanges client credentials for bearer token successfully', function () {
    $tenant = Tenant::factory()->create([
        'client_id' => 'client-123',
        'client_secret' => 'secret-xyz',
    ]);

    $response = $this->postJson('/api/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => 'client-123',
        'client_secret' => 'secret-xyz',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'access_token',
        'token_type',
        'expires_in',
    ]);

    $token = $response->json('access_token');
    $this->assertDatabaseHas('tenant_oauth_tokens', [
        'tenant_id' => $tenant->id,
        'access_token' => $token,
    ]);
});

test('oauth token endpoint rejects invalid client credentials', function () {
    $tenant = Tenant::factory()->create([
        'client_id' => 'client-123',
        'client_secret' => 'secret-xyz',
    ]);

    $response = $this->postJson('/api/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => 'client-123',
        'client_secret' => 'wrong-secret',
    ]);

    $response->assertStatus(401);
    $response->assertJsonPath('error', 'invalid_client');
});

test('oauth token endpoint rejects unsupported grant types', function () {
    $response = $this->postJson('/api/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'client-123',
        'client_secret' => 'secret-xyz',
    ]);

    $response->assertStatus(400);
    $response->assertJsonPath('error', 'unsupported_grant_type');
});

test('custom tools route rejects missing token with 401', function () {
    $response = $this->postJson('/api/webhooks/dispatch', []);
    $response->assertStatus(401);
});

test('custom tools route rejects invalid token with 401', function () {
    $response = $this->withToken('invalid-token')->postJson('/api/webhooks/dispatch', []);
    $response->assertStatus(401);
});

test('custom tools route rejects expired token with 401', function () {
    $tenant = Tenant::factory()->create();
    TenantOAuthToken::create([
        'tenant_id' => $tenant->id,
        'access_token' => 'expired-token-abc',
        'expires_at' => now()->subMinutes(1),
    ]);

    $response = $this->withToken('expired-token-abc')->postJson('/api/webhooks/dispatch', []);
    $response->assertStatus(401);
    $response->assertJsonPath('message', 'Expired bearer token.');
});

test('custom tools route authorizes valid token and resolves tenant shard', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    TenantOAuthToken::create([
        'tenant_id' => $tenant->id,
        'access_token' => 'valid-token-abc',
        'expires_at' => now()->addMinutes(60),
    ]);

    $payload = [
        'function_name' => 'trigger_workflow',
        'event_name' => 'appointment_booked',
        'tenant_id' => $tenant->id,
        'payload' => ['sample' => 'data'],
    ];

    $response = $this->withToken('valid-token-abc')->postJson('/api/webhooks/dispatch', $payload);

    $response->dump();
    $response->assertOk();
    $response->assertJsonPath('status', 'success');
});
