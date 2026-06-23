<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Reset TenantScope state between tests
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('global scope automatically filters queries to active tenant on active sessions', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    // Create records for Tenant A
    $employeeA = Employee::factory()->create(['tenant_id' => $tenantA->id]);
    $availA = Availability::factory()->create(['tenant_id' => $tenantA->id, 'employee_id' => $employeeA->id]);
    $bookingA = Booking::factory()->create(['tenant_id' => $tenantA->id, 'employee_id' => $employeeA->id]);

    // Create records for Tenant B
    $employeeB = Employee::factory()->create(['tenant_id' => $tenantB->id]);
    $availB = Availability::factory()->create(['tenant_id' => $tenantB->id, 'employee_id' => $employeeB->id]);
    $bookingB = Booking::factory()->create(['tenant_id' => $tenantB->id, 'employee_id' => $employeeB->id]);

    // Act: Authenticate as User A
    $this->actingAs($userA);

    // Assert: Tenant A can only query A's resources
    expect(Employee::count())->toBe(1)
        ->and(Employee::first()->id)->toBe($employeeA->id);

    expect(Availability::count())->toBe(1)
        ->and(Availability::first()->id)->toBe($availA->id);

    expect(Booking::count())->toBe(1)
        ->and(Booking::first()->id)->toBe($bookingA->id);
});

test('global scope can be programmatically set via helper', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $employeeA = Employee::factory()->create(['tenant_id' => $tenantA->id]);
    $employeeB = Employee::factory()->create(['tenant_id' => $tenantB->id]);

    // Act: Set tenant B
    TenantScope::setTenantId($tenantB->id);

    // Assert: Only B is visible
    expect(Employee::count())->toBe(1)
        ->and(Employee::first()->id)->toBe($employeeB->id);
});

test('models automatically bind tenant_id on creation', function () {
    $tenantA = Tenant::factory()->create();

    // Act: Set tenant A
    TenantScope::setTenantId($tenantA->id);

    // Create employee without tenant_id attribute
    $employee = Employee::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '123-456-7890',
        'skills' => ['plumbing'],
    ]);

    // Assert: tenant_id was automatically bound
    expect($employee->tenant_id)->toBe($tenantA->id);
});
