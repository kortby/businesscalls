<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;

test('guests are excluded from toggling sandbox mode', function () {
    $response = $this->postJson('/api/settings/toggle-sandbox');
    $response->assertStatus(401);
});

test('authenticated user can toggle sandbox mode state', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->postJson('/api/settings/toggle-sandbox');

    $response->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'is_test_mode' => false,
        ]);

    expect($tenant->fresh()->is_test_mode)->toBeFalse();

    // Verify audit log entry
    $auditLog = AuditLog::where('tenant_id', $tenant->id)
        ->where('action', 'sandbox_toggled')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->payload['is_test_mode'])->toBeFalse();
});

test('stripe checkout is mocked when tenant is in test mode', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->postJson('/api/billing/checkout', [
        'plan' => 'enterprise',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url']);

    expect($response->json('url'))->toContain('checkout=success')
        ->and($response->json('url'))->toContain('test_mode=true')
        ->and($tenant->fresh()->plan)->toBe('enterprise');
});

test('created bookings and call logs automatically bind to the active test mode state', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);

    // Programmatically set tenant context
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    $booking = Booking::create([
        'employee_id' => $employee->id,
        'customer_phone' => '123-456-7890',
        'job_details' => 'HVAC service call',
        'status' => 'booked',
        'scheduled_start' => now(),
    ]);

    expect($booking->is_test_mode)->toBeTrue();

    // Switch tenant to live mode
    $tenant->update(['is_test_mode' => false]);
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'call_id' => 'call-live-1',
        'status' => 'ongoing',
        'customer_phone' => '123-456-7890',
    ]);

    expect($callLog->is_test_mode)->toBeFalse();
});
