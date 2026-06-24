<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view employees list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Bob',
        'last_name' => 'Sponge',
        'phone' => '555-999-9999',
        'skills' => ['cooking'],
        'notification_preference' => 'sms',
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertStatus(200);
});

test('authenticated user can create employee and links user if email is provided', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->post(route('employees.store'), [
        'first_name' => 'Patrick',
        'last_name' => 'Star',
        'phone' => '555-888-8888',
        'skills' => ['sleeping'],
        'notification_preference' => 'email',
        'email' => 'patrick@star.com',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('employees', [
        'first_name' => 'Patrick',
        'last_name' => 'Star',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'patrick@star.com',
        'tenant_id' => $tenant->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'technician_added',
    ]);
});

test('authenticated user can delete employee and it logs removed audit trail', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Squidward',
        'last_name' => 'Tentacles',
        'phone' => '555-777-7777',
        'skills' => ['music'],
        'notification_preference' => 'both',
    ]);

    $response = $this->actingAs($user)->delete(route('employees.destroy', $employee->id));

    $response->assertRedirect();

    $this->assertDatabaseMissing('employees', [
        'id' => $employee->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'technician_removed',
    ]);
});
