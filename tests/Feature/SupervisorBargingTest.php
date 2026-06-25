<?php

use App\Events\SupervisorBarged;
use App\Models\CallLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('guests are excluded from call barging', function () {
    $response = $this->postJson('/api/web-calls/barge', [
        'call_id' => 'call-123',
        'mode' => 'barge',
    ]);
    $response->assertStatus(401);
});

test('non-supervisor users are forbidden from call barging', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => false]);

    $response = $this->actingAs($user)->postJson('/api/web-calls/barge', [
        'call_id' => 'call-123',
        'mode' => 'barge',
    ]);
    $response->assertStatus(403);
});

test('supervisor can barge active calls and trigger broadcast events', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-123',
        'status' => 'ongoing',
        'customer_phone' => '123-456-7890',
        'is_test_mode' => true,
    ]);

    $response = $this->actingAs($user)->postJson('/api/web-calls/barge', [
        'call_id' => 'call-123',
        'mode' => 'barge',
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'call_id' => 'call-123',
            'mode' => 'barge',
        ]);

    Event::assertDispatched(SupervisorBarged::class, function ($event) use ($tenant, $user) {
        return $event->tenantId === $tenant->id
            && $event->callId === 'call-123'
            && $event->mode === 'barge'
            && $event->supervisorName === $user->name;
    });

    expect($callLog->fresh()->call_end_reason)->toBe('supervisor_barged');
});

test('supervisors cannot barge call logs belonging to other tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id, 'is_supervisor' => true]);

    // Call Log in Tenant B
    $callLogB = CallLog::create([
        'tenant_id' => $tenantB->id,
        'call_id' => 'call-other',
        'status' => 'ongoing',
        'customer_phone' => '555-0100',
        'is_test_mode' => true,
    ]);

    $response = $this->actingAs($userA)->postJson('/api/web-calls/barge', [
        'call_id' => 'call-other',
        'mode' => 'barge',
    ]);

    // Query should fail to locate due to tenant isolation or return forbidden
    $response->assertStatus(404);
});

test('supervisors can view supervisor hud and get inertia payload', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);

    $response = $this->actingAs($user)->get('/admin/supervisor-hud');

    $response->assertOk();
});

test('guests are redirected from supervisor hud', function () {
    $response = $this->get('/admin/supervisor-hud');
    $response->assertRedirect();
});
