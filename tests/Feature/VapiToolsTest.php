<?php

use App\AI\Tools\BookAppointmentTool;
use App\AI\Tools\CheckAvailabilityTool;
use App\AI\Tools\GetAvailabilitySlotsTool;
use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\Models\Availability;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('Vapi Tool get_first_three_availabilities returns available slots', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $tomorrow = Carbon::tomorrow();
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $tomorrow->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new GetFirstThreeAvailabilitiesTool;
    $result = $tool->handle($tenant->id, 'plumbing');

    expect($result['status'])->toBe('success')
        ->and($result['count'])->toBeGreaterThan(0)
        ->and(count($result['formatted_options']))->toBeGreaterThan(0);
});

test('Vapi Tool get_available_slots returns slots for specific date and service', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['hvac'],
    ]);

    $targetDate = Carbon::today()->addDays(2);
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $targetDate->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new GetAvailabilitySlotsTool;
    $result = $tool->handle($tenant->id, $targetDate->toDateString(), 'hvac');

    expect($result['status'])->toBe('success')
        ->and($result['slots_count'])->toBeGreaterThan(0)
        ->and($result['date'])->toBe($targetDate->toDateString());
});

test('Vapi Tool check_availability verifies tech availability and schema', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['electrical'],
    ]);

    $tomorrow = Carbon::tomorrow();
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $tomorrow->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'is_active' => true,
    ]);

    $tool = new CheckAvailabilityTool;
    $result = $tool->handle($tenant->id, 'electrical');

    expect($tool->description())->not->toBeEmpty()
        ->and($tool->schema())->toHaveKey('tenant_id')
        ->and($result['status'])->toBe('success')
        ->and($result['count'])->toBeGreaterThan(0);
});

test('Vapi Tool book_appointment successfully commits booking to database', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $bookingDate = Carbon::tomorrow()->setHour(10)->setMinute(0)->setSecond(0);
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $bookingDate->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $tool = new BookAppointmentTool;
    $result = $tool->handle(
        (string) $tenant->id,
        '+16195551234',
        'Leaking pipe under kitchen sink',
        $bookingDate->toDateTimeString(),
        $employee->id
    );

    expect($result['status'])->toBe('success')
        ->and($result['booking_id'])->not->toBeNull();

    $this->assertDatabaseHas('bookings', [
        'id' => $result['booking_id'],
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+16195551234',
        'status' => 'booked',
    ]);
});

test('Vapi Webhook Dispatch endpoint executes all 4 tools via Vapi tool-calls format', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $tomorrow = Carbon::tomorrow()->setHour(11)->setMinute(0)->setSecond(0);
    Availability::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $tomorrow->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // 1. Test get_first_three_availabilities
    $res1 = $this->postJson('/api/webhooks/dispatch', [
        'tenant_id' => $tenant->id,
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'vapi-call-1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_first_three_availabilities',
                        'arguments' => ['service_type' => 'plumbing', 'tenant_id' => $tenant->id],
                    ],
                ],
            ],
        ],
    ]);
    $res1->assertOk()->assertJsonPath('results.0.result.status', 'success');

    // 2. Test get_available_slots
    $res2 = $this->postJson('/api/webhooks/dispatch', [
        'tenant_id' => $tenant->id,
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'vapi-call-2',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_available_slots',
                        'arguments' => ['date' => $tomorrow->toDateString(), 'service_type' => 'plumbing', 'tenant_id' => $tenant->id],
                    ],
                ],
            ],
        ],
    ]);
    $res2->assertOk()->assertJsonPath('results.0.result.status', 'success');

    // 3. Test check_availability
    $res3 = $this->postJson('/api/webhooks/dispatch', [
        'tenant_id' => $tenant->id,
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'vapi-call-3',
                    'type' => 'function',
                    'function' => [
                        'name' => 'check_availability',
                        'arguments' => [
                            'service_type' => 'plumbing',
                            'requested_time' => $tomorrow->toDateTimeString(),
                            'tenant_id' => $tenant->id,
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $res3->assertOk()->assertJsonPath('results.0.result.status', 'success');

    // 4. Test book_appointment
    $res4 = $this->postJson('/api/webhooks/dispatch', [
        'tenant_id' => $tenant->id,
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'vapi-call-4',
                    'type' => 'function',
                    'function' => [
                        'name' => 'book_appointment',
                        'arguments' => [
                            'tenant_id' => $tenant->id,
                            'customer_phone' => '+16195559876',
                            'customer_address' => '456 Ocean Blvd',
                            'job_details' => 'Water heater replacement',
                            'requested_time' => $tomorrow->toDateTimeString(),
                            'service_type' => 'plumbing',
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $res4->assertOk()->assertJsonPath('results.0.result.status', 'success');
});
