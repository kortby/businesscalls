<?php

use App\Models\Tenant;
use App\Models\User;

test('guests are excluded from generating webRTC token', function () {
    $response = $this->postJson('/api/web-calls/token');
    $response->assertStatus(401);
});

test('authenticated users with tenant can generate vapi public key token payload', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // Explicitly set the provider to vapi for testing the fallback response
    config(['services.telephony.provider' => 'vapi']);

    $this->actingAs($user);

    $response = $this->postJson('/api/web-calls/token');

    $response->assertOk()
        ->assertJsonStructure([
            'provider',
            'access_token',
            'assistant_id',
        ])
        ->assertJsonFragment([
            'provider' => 'vapi',
        ]);
});
