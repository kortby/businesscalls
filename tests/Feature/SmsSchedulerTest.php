<?php

use App\Ai\Text;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
    Text::$mockResponse = null;
});

test('sms webhook schedules a technician booking automatically if availability matches', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Create technician with skills matching
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'skills' => ['ac-diagnostics'],
    ]);

    // Create availability for this technician
    // e.g. Thursday (day of week: 4) between 08:00 and 17:00
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 4, // Thursday
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // Thursday, June 25th 2026 is Day of Week 4
    Text::$mockResponse = json_encode([
        'trade_category' => 'ac-diagnostics',
        'requested_time' => '2026-06-25 10:00:00',
    ]);

    $response = $this->postJson(route('webhook.sms', ['tenant_id' => $tenant->id]), [
        'From' => '+15559876543',
        'Body' => 'I need an AC diagnostics service on Thursday June 25th at 10 AM',
    ]);

    $response->assertStatus(200);
    $this->assertStringContainsString('Dispatch Confirmed!', $response->getContent());
    $this->assertStringContainsString('John', $response->getContent());

    // Verify booking in database
    $booking = Booking::where('employee_id', $employee->id)->first();
    expect($booking)->not->toBeNull()
        ->and($booking->status)->toBe('booked')
        ->and(Carbon::parse($booking->scheduled_start)->format('Y-m-d H:i:s'))->toBe('2026-06-25 10:00:00');
});

test('sms webhook prevents booking if time slot overlaps with 1.5-hour collision buffer', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Create technician
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Jane',
        'skills' => ['plumbing'],
    ]);

    // Availability: Thursday 08:00 to 17:00
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => 4,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // Create a conflicting booking already at 11:00 AM on Thursday, June 25th
    Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15559999999',
        'job_details' => 'Existing job',
        'status' => 'booked',
        'scheduled_start' => '2026-06-25 11:00:00',
    ]);

    // Request booking at 10:00 AM (only 1 hour before - overlaps within 1.5-hour travel buffer)
    Text::$mockResponse = json_encode([
        'trade_category' => 'plumbing',
        'requested_time' => '2026-06-25 10:00:00',
    ]);

    $response = $this->postJson(route('webhook.sms', ['tenant_id' => $tenant->id]), [
        'From' => '+15559876543',
        'Body' => 'I need plumbing service on June 25th at 10 AM',
    ]);

    $response->assertStatus(200);
    $this->assertStringContainsString('No available technician with skill', $response->getContent());

    // Verify NO new booking was created at 10:00 AM
    $newBooking = Booking::where('employee_id', $employee->id)
        ->where('scheduled_start', '2026-06-25 10:00:00')
        ->first();
    expect($newBooking)->toBeNull();
});
