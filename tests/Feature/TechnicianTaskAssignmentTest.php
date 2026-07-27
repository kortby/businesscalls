<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\DraftTask;
use App\Models\Employee;
use App\Models\ServiceJob;
use App\Models\Tenant;

test('assignTaskToTechnician executes automated 4-step pipeline', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id, 'first_name' => 'John']);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15559876543',
        'job_details' => 'HVAC Compressor Diagnostic',
    ]);

    $job = $booking->assignTaskToTechnician();

    expect($job)->toBeInstanceOf(ServiceJob::class);

    // 1. Check Customer auto-creation
    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'phone' => '+15559876543',
    ]);

    // 2. Check ServiceJob creation
    $this->assertDatabaseHas('service_jobs', [
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'title' => 'HVAC Compressor Diagnostic',
    ]);

    // 3. Check DraftTask task assignment
    $this->assertDatabaseHas('draft_tasks', [
        'tenant_id' => $tenant->id,
        'booking_id' => $booking->id,
        'task_type' => 'technician_dispatch',
    ]);
});
