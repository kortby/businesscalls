<?php

use App\AI\Tools\BookAppointmentTool;
use App\AI\Tools\CancelBookingTool;
use App\AI\Tools\CheckAvailabilityTool;
use App\AI\Tools\CheckInventoryTool;
use App\AI\Tools\CheckTechnicianEtaTool;
use App\AI\Tools\GetAvailabilitySlotsTool;
use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\AI\Tools\LookupBookingTool;
use App\AI\Tools\RescheduleAppointmentTool;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('CheckAvailabilityTool executes natively and returns available slots', function () {
    $tenant = Tenant::factory()->create(['slug' => 'test-hvac']);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => Carbon::tomorrow()->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new CheckAvailabilityTool;
    $result = $tool->handle($tenant->id, 'plumbing');

    expect($result['status'])->toBe('success')
        ->and($result['count'])->toBeGreaterThan(0)
        ->and(count($result['options']))->toBeGreaterThan(0);
});

test('GetFirstThreeAvailabilitiesTool returns top 3 slots for voice assistant', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => Carbon::tomorrow()->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new GetFirstThreeAvailabilitiesTool;
    $result = $tool->handle($tenant->id);

    expect($result['status'])->toBe('success')
        ->and(count($result['formatted_options']))->toBeGreaterThan(0);
});

test('GetAvailabilitySlotsTool returns open slots for specific date', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $tomorrow = Carbon::tomorrow();

    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $tomorrow->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '15:00:00',
        'is_active' => true,
    ]);

    $tool = new GetAvailabilitySlotsTool;
    $result = $tool->handle($tenant->id, $tomorrow->toDateString());

    expect($result['status'])->toBe('success')
        ->and($result['slots_count'])->toBeGreaterThan(0);
});

test('CreateBookingTool and BookAppointmentTool create bookings natively', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $scheduledStart = Carbon::tomorrow()->setTime(10, 0, 0);

    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $scheduledStart->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new BookAppointmentTool;
    $result = $tool->handle(
        $tenant->id,
        '+15550009999',
        'Fix leaky pipe in kitchen',
        $scheduledStart->toIso8601String(),
        $employee->id
    );

    expect($result['status'])->toBe('success')
        ->and($result['booking_id'])->toBeGreaterThan(0);
});

test('LookupBookingTool and RescheduleAppointmentTool manage customer bookings', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $start = Carbon::tomorrow()->setTime(9, 0, 0);

    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $start->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15551234567',
        'scheduled_start' => $start,
    ]);

    // Lookup
    $lookupTool = new LookupBookingTool;
    $lookupRes = $lookupTool->handle($tenant->id, '+15551234567');
    expect($lookupRes['status'])->toBe('success')
        ->and($lookupRes['count'])->toBe(1);

    // Reschedule
    $newTime = Carbon::tomorrow()->setTime(14, 0, 0);
    $rescheduleTool = new RescheduleAppointmentTool;
    $rescheduleRes = $rescheduleTool->handle($tenant->id, $booking->id, $newTime->toIso8601String());

    expect($rescheduleRes['status'])->toBe('success');
});

test('CancelBookingTool, CheckTechnicianEtaTool, and CheckInventoryTool execute natively', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
    ]);

    // ETA check
    $etaTool = new CheckTechnicianEtaTool;
    $etaRes = $etaTool->handle($tenant->id, $booking->id);
    expect($etaRes['status'])->toBe('success')
        ->and($etaRes['technician_name'])->not->toBeEmpty();

    // Inventory check
    $invTool = new CheckInventoryTool;
    $invRes = $invTool->handle($tenant->id, 'faucet');
    expect($invRes['status'])->toBe('success')
        ->and($invRes['in_stock'])->toBeTrue();

    // Cancel booking
    $cancelTool = new CancelBookingTool;
    $cancelRes = $cancelTool->handle($tenant->id, $booking->id);
    expect($cancelRes['status'])->toBe('success');
});
