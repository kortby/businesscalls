<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;

test('admin can view, create, update, and delete employees', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'admin',
        'is_supervisor' => true,
    ]);

    // View index
    $response = $this->actingAs($admin)->get(route('employees.index'));
    $response->assertOk();

    // Create employee
    $storePayload = [
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'phone' => '+15551234567',
        'role' => 'supervisor',
        'skills' => ['hvac', 'electrical'],
        'notification_preference' => 'sms',
        'email' => 'alice.smith@example.com',
    ];

    $response = $this->actingAs($admin)->post(route('employees.store'), $storePayload);
    $response->assertRedirect();
    expect(Employee::where('tenant_id', $tenant->id)->where('first_name', 'Alice')->exists())->toBeTrue();

    $employee = Employee::where('tenant_id', $tenant->id)->where('first_name', 'Alice')->first();

    // Update employee
    $updatePayload = [
        'first_name' => 'Alice Updated',
        'last_name' => 'Smith',
        'phone' => '+15551234567',
        'role' => 'admin',
        'skills' => ['hvac', 'master_plumber'],
        'notification_preference' => 'both',
    ];

    $response = $this->actingAs($admin)->put(route('employees.update', $employee->id), $updatePayload);
    $response->assertRedirect();
    expect($employee->fresh()->first_name)->toBe('Alice Updated');

    // Delete employee
    $response = $this->actingAs($admin)->delete(route('employees.destroy', $employee->id));
    $response->assertRedirect();
    expect(Employee::where('id', $employee->id)->exists())->toBeFalse();
});

test('supervisor can create and update employees but not cross tenants', function () {
    $tenant = Tenant::factory()->create();
    $supervisor = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'supervisor',
        'is_supervisor' => true,
    ]);

    $storePayload = [
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'phone' => '+15559998888',
        'role' => 'technician',
        'skills' => ['plumbing'],
        'notification_preference' => 'email',
    ];

    $response = $this->actingAs($supervisor)->post(route('employees.store'), $storePayload);
    $response->assertRedirect();

    $employee = Employee::where('tenant_id', $tenant->id)->where('first_name', 'Bob')->first();
    expect($employee)->not->toBeNull();

    // Cross-tenant update attempt should fail (or be scoped)
    $otherTenant = Tenant::factory()->create();
    $otherEmployee = Employee::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->actingAs($supervisor)->put(route('employees.update', $otherEmployee->id), [
        'first_name' => 'Hacked',
        'last_name' => 'Name',
        'phone' => '+15550001111',
        'notification_preference' => 'sms',
    ]);
    expect(in_array($response->status(), [403, 404]))->toBeTrue();
});

test('technician can update own profile but is forbidden from creating or deleting employees', function () {
    $tenant = Tenant::factory()->create();
    $techUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'technician',
        'is_supervisor' => false,
    ]);

    $techEmployee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $techUser->id,
        'first_name' => 'Charlie',
        'last_name' => 'Tech',
        'role' => 'technician',
    ]);

    $otherEmployee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Dave',
        'role' => 'technician',
    ]);

    // Create attempt -> Forbidden
    $response = $this->actingAs($techUser)->post(route('employees.store'), [
        'first_name' => 'Forbidden',
        'last_name' => 'Employee',
        'phone' => '+15553334444',
        'role' => 'technician',
        'notification_preference' => 'sms',
    ]);
    $response->assertForbidden();

    // Delete attempt -> Forbidden
    $response = $this->actingAs($techUser)->delete(route('employees.destroy', $otherEmployee->id));
    $response->assertForbidden();

    // Update other employee -> Forbidden
    $response = $this->actingAs($techUser)->put(route('employees.update', $otherEmployee->id), [
        'first_name' => 'Unauthorized Edit',
        'last_name' => 'Dave',
        'phone' => $otherEmployee->phone,
        'notification_preference' => 'sms',
    ]);
    $response->assertForbidden();

    // Update own profile -> Allowed
    $response = $this->actingAs($techUser)->put(route('employees.update', $techEmployee->id), [
        'first_name' => 'Charlie Updated',
        'last_name' => 'Tech',
        'phone' => '+15557778888',
        'notification_preference' => 'both',
    ]);
    $response->assertRedirect();
    expect($techEmployee->fresh()->first_name)->toBe('Charlie Updated');
});
