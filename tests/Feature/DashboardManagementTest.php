<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Reset TenantScope state between tests
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('authenticated user can create shift availability', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);

    $response = $this->post(route('availabilities.store'), [
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    // Set scope to verify
    TenantScope::setTenantId($tenant->id);
    $this->assertDatabaseHas('availabilities', [
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
});

test('authenticated user can delete shift availability', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    $avail = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('availabilities.destroy', $avail->id));

    $response->assertRedirect();

    TenantScope::setTenantId($tenant->id);
    $this->assertDatabaseMissing('availabilities', ['id' => $avail->id]);
});

test('manual booking validation checks shift availability', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    // No shift availability on Tuesday (2026-06-23 is Tuesday, formatting 'w' gives 2)
    $this->actingAs($user);

    $response = $this->post(route('bookings.store'), [
        'employee_id' => $employee->id,
        'customer_phone' => '555-019-2834',
        'job_details' => 'Leaky pipe',
        'scheduled_start' => '2026-06-23 10:00:00',
    ]);

    $response->assertSessionHasErrors(['scheduled_start']);

    TenantScope::setTenantId($tenant->id);
    expect(Booking::count())->toBe(0);
});

test('manual booking validation checks 1.5h overlaps', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    // Active shift availability on Tuesday
    $avail = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 2, // Tuesday
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    // Existing booking on Tuesday at 10:00
    $existingBooking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '555-111-2222',
        'job_details' => 'Existing booking',
        'status' => 'booked',
        'scheduled_start' => '2026-06-23 10:00:00',
    ]);

    $this->actingAs($user);

    // Act: Attempt booking at 11:00 (within 1.5h buffer)
    $response = $this->post(route('bookings.store'), [
        'employee_id' => $employee->id,
        'customer_phone' => '555-019-2834',
        'job_details' => 'New booking',
        'scheduled_start' => '2026-06-23 11:00:00',
    ]);

    $response->assertSessionHasErrors(['scheduled_start']);

    TenantScope::setTenantId($tenant->id);
    expect(Booking::count())->toBe(1); // Only the existing one
});

test('manual booking succeeds if technician is available and has no conflicts', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    // Active shift availability on Tuesday
    $avail = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 2, // Tuesday
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $this->actingAs($user);

    $response = $this->post(route('bookings.store'), [
        'employee_id' => $employee->id,
        'customer_phone' => '555-019-2834',
        'job_details' => 'Leaky pipe repair',
        'scheduled_start' => '2026-06-23 10:00:00',
    ]);

    $response->assertRedirect();

    TenantScope::setTenantId($tenant->id);
    $this->assertDatabaseHas('bookings', [
        'employee_id' => $employee->id,
        'customer_phone' => '555-019-2834',
        'job_details' => 'Leaky pipe repair',
        'status' => 'booked',
    ]);
});

test('authenticated user can cancel booking', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'booked',
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('bookings.destroy', $booking->id));

    $response->assertRedirect();

    TenantScope::setTenantId($tenant->id);
    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
});

test('shift availability store validation prevents overlapping shifts', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
    ]);

    $this->actingAs($user);

    // Attempt to store overlapping shift: 10:00 to 14:00 (overlaps with 08:00-12:00)
    $response = $this->post(route('availabilities.store'), [
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '10:00',
        'end_time' => '14:00',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors(['start_time']);

    TenantScope::setTenantId($tenant->id);
    expect(Availability::count())->toBe(1);
});

test('authenticated user can view availabilities index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);

    $response = $this->get(route('availabilities.index'));

    $response->assertOk();
});

test('authenticated user can update availability shift', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    $avail = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->put(route('availabilities.update', $avail->id), [
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '13:00',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    TenantScope::setTenantId($tenant->id);
    $avail->refresh();
    expect($avail->start_time)->toBe('09:00');
    expect($avail->end_time)->toBe('13:00');
});

test('availability update prevents overlapping shifts', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    // Shift A
    $availA = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
    ]);
    // Shift B
    $availB = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '13:00:00',
        'end_time' => '17:00:00',
    ]);

    $this->actingAs($user);

    // Try to update Shift B to overlap with Shift A (e.g. 11:00 to 15:00)
    $response = $this->put(route('availabilities.update', $availB->id), [
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'start_time' => '11:00',
        'end_time' => '15:00',
    ]);

    $response->assertSessionHasErrors(['start_time']);
});

test('availability update tenant isolation prevents updating other tenant shift', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $employeeB = Employee::factory()->create(['tenant_id' => $tenantB->id]);

    TenantScope::setTenantId($tenantB->id);
    $availB = Availability::factory()->create([
        'tenant_id' => $tenantB->id,
        'employee_id' => $employeeB->id,
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
    ]);

    TenantScope::setTenantId(null);
    session()->forget('tenant_id');

    $this->actingAs($userA);

    // Act: Attempt to update tenant B's shift as Tenant A
    $response = $this->put(route('availabilities.update', $availB->id), [
        'employee_id' => $employeeB->id, // this employee is tenant B's employee
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '13:00',
    ]);

    // ModelNotFoundException since Tenant B's shift is filtered out by TenantScope for User A
    $response->assertNotFound();
});

test('authenticated user can view bookings index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user);

    $response = $this->get(route('bookings.index'));

    $response->assertOk();
});

test('authenticated user can update booking', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    $avail = Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 2, // Tuesday
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '555-111-2222',
        'job_details' => 'Old details',
        'status' => 'booked',
        'scheduled_start' => '2026-06-23 10:00:00',
    ]);

    $this->actingAs($user);

    $response = $this->put(route('bookings.update', $booking->id), [
        'employee_id' => $employee->id,
        'customer_phone' => '555-999-8888',
        'job_details' => 'New details',
        'scheduled_start' => '2026-06-23 11:00:00',
    ]);

    $response->assertRedirect();

    TenantScope::setTenantId($tenant->id);
    $booking->refresh();
    expect($booking->customer_phone)->toBe('555-999-8888');
    expect($booking->job_details)->toBe('New details');
    expect(Carbon::parse($booking->scheduled_start)->format('H:i'))->toBe('11:00');
});

test('booking update tenant isolation prevents updating other tenant booking', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $employeeB = Employee::factory()->create(['tenant_id' => $tenantB->id]);

    TenantScope::setTenantId($tenantB->id);
    $bookingB = Booking::factory()->create([
        'tenant_id' => $tenantB->id,
        'employee_id' => $employeeB->id,
        'status' => 'booked',
    ]);

    TenantScope::setTenantId(null);
    session()->forget('tenant_id');

    $this->actingAs($userA);

    $response = $this->put(route('bookings.update', $bookingB->id), [
        'employee_id' => $employeeB->id,
        'customer_phone' => '555-999-8888',
        'job_details' => 'Hacked details',
        'scheduled_start' => '2026-06-23 11:00:00',
    ]);

    $response->assertNotFound();
});
