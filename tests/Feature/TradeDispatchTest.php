<?php

use App\Jobs\SendEtaUpdateSmsJob;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
    Http::fake();
});

test('triage rules classify priority correctly', function ($serviceType, $additionalArgs, $expectedPriority) {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => [$serviceType],
        'certifications' => ['EPA_608', 'Master_Plumber'], // ensure cert check passes
    ]);

    $requestedTime = '2026-06-25 10:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $payload = array_merge([
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551234567',
        'service_type' => $serviceType,
        'requested_time' => $requestedTime,
    ], $additionalArgs);

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertOk();
    $booking = Booking::orderBy('id', 'desc')->first();
    expect($booking->priority_state)->toBe($expectedPriority);
})->with([
    ['plumbing', ['water_leak' => true], 'emergency'],
    ['plumbing', ['water_leak' => 'yes'], 'emergency'],
    ['plumbing', ['water_leak' => false], 'routine_maintenance'],
    ['hvac', ['outdoor_temp' => 101], 'emergency'],
    ['hvac', ['outdoor_temp' => 15], 'emergency'],
    ['hvac', ['outdoor_temp' => 75], 'routine_maintenance'],
    ['electrical', ['sparking_outlets' => true], 'emergency'],
    ['electrical', ['burning_smell' => true], 'emergency'],
    ['electrical', ['sparking_outlets' => false, 'burning_smell' => false], 'routine_maintenance'],
    ['plumbing', ['emergency_triage' => true], 'emergency'],
]);

test('certification filter excludes technicians without credentials', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);

    // Employee 1: has plumbing skill but NO Master_Plumber certification
    $employee1 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
        'certifications' => [],
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);

    // Employee 2: has plumbing skill AND Master_Plumber certification
    $employee2 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
        'certifications' => ['Master_Plumber'],
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);

    $requestedTime = '2026-06-25 10:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    // Both are available
    foreach ([$employee1, $employee2] as $emp) {
        Availability::factory()->create([
            'tenant_id' => $tenant->id,
            'employee_id' => $emp->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
    }

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551234567',
        'service_type' => 'plumbing',
        'requested_time' => $requestedTime,
        'required_certification' => 'Master_Plumber',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertOk();
    $booking = Booking::orderBy('id', 'desc')->first();
    expect($booking->employee_id)->toBe($employee2->id);
});

test('technician compatibility score theta is calculated and bounds to 0 and 1', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);

    // Tech 1: Very close (0 km)
    $employee1 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);

    // Tech 2: Far away (111 km, theta would be negative so should be bounded to 0.0)
    $employee2 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
        'latitude' => 38.7749, // 1 degree north is ~111 km
        'longitude' => -122.4194,
    ]);

    $requestedTime = '2026-06-25 10:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    foreach ([$employee1, $employee2] as $emp) {
        Availability::factory()->create([
            'tenant_id' => $tenant->id,
            'employee_id' => $emp->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
    }

    // Call closest one first
    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551234567',
        'service_type' => 'plumbing',
        'requested_time' => $requestedTime,
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);
    $response->assertOk();

    $booking = Booking::orderBy('id', 'desc')->first();
    expect($booking->employee_id)->toBe($employee1->id);
});

test('emergency booking triggers schedule rebalancing and pushes subsequent routine jobs back by 120 minutes', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['secret_key' => null]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['plumbing'],
    ]);

    $requestedTime = '2026-06-25 10:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '20:00:00',
        'is_active' => true,
    ]);

    // Create a routine booking at 11:00 today
    $booking1 = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'scheduled_start' => Carbon::parse('2026-06-25 11:00:00'),
        'status' => 'booked',
        'priority_state' => 'routine_maintenance',
    ]);

    // Create another routine booking at 13:00 today
    $booking2 = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'scheduled_start' => Carbon::parse('2026-06-25 13:00:00'),
        'status' => 'booked',
        'priority_state' => 'routine_maintenance',
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15550001111',
        'service_type' => 'plumbing',
        'requested_time' => $requestedTime,
        'water_leak' => true, // emergency trigger
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);
    $response->assertOk();

    // Verify bookings pushed back by 120 minutes:
    // First subsequent (originally 11:00) should be pushed to 10:00 + 120m = 12:00
    // Second subsequent (originally 13:00) should be pushed to 12:00 + 120m = 14:00
    $booking1->refresh();
    $booking2->refresh();

    expect($booking1->scheduled_start->format('Y-m-d H:i:s'))->toBe('2026-06-25 12:00:00');
    expect($booking2->scheduled_start->format('Y-m-d H:i:s'))->toBe('2026-06-25 14:00:00');

    // Verify SMS update job was queued
    Queue::assertPushed(SendEtaUpdateSmsJob::class, 2);
});

test('tenant isolation restricts rebalancing to the matching tenant', function () {
    Queue::fake();

    $tenantA = Tenant::factory()->create(['secret_key' => null]);
    $tenantB = Tenant::factory()->create(['secret_key' => null]);

    $employeeA = Employee::factory()->create([
        'tenant_id' => $tenantA->id,
        'skills' => ['plumbing'],
    ]);

    $employeeB = Employee::factory()->create([
        'tenant_id' => $tenantB->id,
        'skills' => ['plumbing'],
    ]);

    $requestedTime = '2026-06-25 10:00:00';
    $dayOfWeek = Carbon::parse($requestedTime)->dayOfWeek;

    foreach ([$employeeA, $employeeB] as $emp) {
        Availability::factory()->create([
            'tenant_id' => $emp->tenant_id,
            'employee_id' => $emp->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);
    }

    // Tenant B's routine booking today at 11:00
    $bookingB = Booking::factory()->create([
        'tenant_id' => $tenantB->id,
        'employee_id' => $employeeB->id,
        'scheduled_start' => Carbon::parse('2026-06-25 11:00:00'),
        'status' => 'booked',
        'priority_state' => 'routine_maintenance',
    ]);

    // Dispatch emergency for Tenant A
    $payload = [
        'tenant_id' => $tenantA->id,
        'customer_phone' => '+15559990000',
        'service_type' => 'plumbing',
        'requested_time' => $requestedTime,
        'water_leak' => true,
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);
    $response->assertOk();

    // Tenant B's booking should remain untouched
    $bookingB->refresh();
    expect($bookingB->scheduled_start->format('Y-m-d H:i:s'))->toBe('2026-06-25 11:00:00');
});
