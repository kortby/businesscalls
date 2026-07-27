<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;

test('authenticated user can convert booking to customer and service job', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15551234567',
        'job_details' => 'Emergency AC Leak Repair',
        'triage_notes' => 'Freon level low, leaking condensate drain.',
    ]);

    $response = $this->actingAs($user)
        ->post(route('bookings.convert-to-job', $booking));

    $response->assertRedirect(route('jobs.index'));

    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'phone' => '+15551234567',
    ]);

    $customer = Customer::where('phone', '+15551234567')->first();

    $this->assertDatabaseHas('service_jobs', [
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'title' => 'Emergency AC Leak Repair',
    ]);
});
