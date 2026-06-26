<?php

use App\Models\CallLog;
use App\Models\Tenant;
use App\Models\User;

test('non-supervisor users are forbidden from admin pages', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);
    $this->actingAs($user);

    $routes = [
        'admin.dispatch-map',
        'admin.call-monitor',
        'admin.supervisor-hud',
        'admin.status-hud',
        'admin.preflight',
        'admin.diagnostics',
        'admin.sla-diagnostics',
        'admin.health',
        'admin.callflow',
        'admin.saas-profit',
        'admin.onboarding',
        'admin.onboarding-board',
        'admin.streak-hub',
        'admin.csat-feedback',
        'admin.achievements',
        'admin.leaderboard',
        'admin.billing-hub',
        'admin.loyalty',
        'admin.audit-logs',
        'admin.mascot-shop',
        'admin.integrations',
        'admin.experiments',
    ];

    foreach ($routes as $route) {
        $response = $this->get(route($route));
        $response->assertStatus(403);
    }
});

test('non-supervisor users are forbidden from prompt settings', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);
    $this->actingAs($user);

    $response = $this->get(route('settings.prompt.edit'));
    $response->assertStatus(403);

    $response = $this->patch(route('settings.prompt.update'), [
        'ai_prompt' => 'New instructions',
        'emergency_fee' => '100',
    ]);
    $response->assertStatus(403);
});

test('non-supervisor users are forbidden from billing settings', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);
    $this->actingAs($user);

    $response = $this->get(route('settings.billing.index'));
    $response->assertStatus(403);
});

test('non-supervisor users are forbidden from supervisor-only API routes', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);
    $this->actingAs($user);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-123',
        'status' => 'ongoing',
        'customer_phone' => '555-0100',
        'is_test_mode' => true,
    ]);

    // Barge API
    $response = $this->postJson('/api/web-calls/barge', [
        'call_id' => 'call-123',
        'mode' => 'barge',
    ]);
    $response->assertStatus(403);

    // Whisper API
    $response = $this->postJson('/api/web-calls/whisper', [
        'call_id' => 'call-123',
        'instruction' => 'Whisper instruction',
    ]);
    $response->assertStatus(403);

    // Redact API
    $response = $this->postJson("/api/call-logs/{$callLog->id}/redact");
    $response->assertStatus(403);

    // Branded Caller ID API
    $response = $this->postJson('/api/settings/branded-caller-id', [
        'phone_number' => '555-0100',
        'business_name' => 'Acme Corp',
    ]);
    $response->assertStatus(403);
});
