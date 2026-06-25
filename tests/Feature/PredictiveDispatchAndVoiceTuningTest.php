<?php

use App\Events\TechnicianAllocated;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PredictiveAllocationService;
use App\Services\VoiceTuningService;
use Illuminate\Support\Facades\Event;

test('PredictiveAllocationService distributes jobs based on rolling workload and travel time metrics', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['is_test_mode' => true]);

    // Create 2 technicians
    $techA = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'skills' => ['hvac'],
    ]);

    $techB = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'skills' => ['hvac'],
    ]);

    // Give techA a heavy workload in the last 7 days (e.g. 2 completed bookings)
    Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $techA->id,
        'customer_phone' => '555-0101',
        'status' => 'completed',
        'completed_at' => now()->subDays(2),
        'en_route_at' => now()->subDays(2)->subHours(3),
        'scheduled_start' => now()->subDays(2)->subHours(3),
        'travel_time' => 15,
        'is_test_mode' => true,
        'job_details' => 'AC Maintenance',
    ]);

    Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $techA->id,
        'customer_phone' => '555-0102',
        'status' => 'completed',
        'completed_at' => now()->subDays(3),
        'en_route_at' => now()->subDays(3)->subHours(2),
        'scheduled_start' => now()->subDays(3)->subHours(2),
        'travel_time' => 10,
        'is_test_mode' => true,
        'job_details' => 'Heater Tune-up',
    ]);

    // TechB has no completed bookings (workload = 0)
    // Create a new booking initially assigned to TechA (which we expect to re-route to TechB)
    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $techA->id,
        'customer_phone' => '555-9999',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
        'is_test_mode' => true,
        'job_details' => 'HVAC Install',
    ]);

    $service = app(PredictiveAllocationService::class);
    $allocatedTech = $service->allocateTechnician($tenant, $booking, 'hvac');

    // TechB should be selected because their workload is lighter than TechA
    expect($allocatedTech->id)->toBe($techB->id);
    expect($booking->fresh()->employee_id)->toBe($techB->id);

    Event::assertDispatched(TechnicianAllocated::class, function ($event) use ($tenant, $techB) {
        return $event->tenantId === $tenant->id
            && $event->employeeId === $techB->id
            && $event->status === 'allocated';
    });
});

test('PredictiveAllocationService isolates technician matching to active tenant boundaries', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $techA = Employee::factory()->create([
        'tenant_id' => $tenantA->id,
        'skills' => ['plumbing'],
    ]);

    $techB = Employee::factory()->create([
        'tenant_id' => $tenantB->id,
        'skills' => ['plumbing'],
    ]);

    // Create booking in Tenant A, initially assigned to B's tech (to test boundary swap)
    $bookingA = Booking::create([
        'tenant_id' => $tenantA->id,
        'employee_id' => $techB->id,
        'customer_phone' => '555-9999',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
        'is_test_mode' => true,
        'job_details' => 'Leak Repair',
    ]);

    $service = app(PredictiveAllocationService::class);
    $allocated = $service->allocateTechnician($tenantA, $bookingA, 'plumbing');

    // Must swap to Tech A (from same tenant) and isolate from Tenant B's parameters
    expect($allocated->id)->toBe($techA->id);
});

test('VoiceTuningService applies calming pitch and speed overrides during customer distress sentiment', function () {
    $tenant = Tenant::factory()->create(['is_test_mode' => true]);
    $service = app(VoiceTuningService::class);

    // Normal sentiment
    $result = $service->calibrateVoice($tenant, 'call-111', 'normal', [], 'cartesia');
    expect($result['updated'])->toBeFalse();

    // Distress triggers
    $resultDistressed = $service->calibrateVoice($tenant, 'call-222', 'distressed', ['water', 'leak'], 'cartesia');
    expect($resultDistressed['updated'])->toBeTrue();
    expect($resultDistressed['overrides']['speed'])->toBe(0.90);
    expect($resultDistressed['overrides']['emotion']['calm'])->toBe(0.8);

    // ElevenLabs override triggers
    $resultEleven = $service->calibrateVoice($tenant, 'call-333', 'angry', ['fire'], 'elevenlabs');
    expect($resultEleven['updated'])->toBeTrue();
    expect($resultEleven['overrides']['stability'])->toBe(0.85);
});

test('guests are redirected from onboarding-setup route', function () {
    $response = $this->get('/admin/onboarding-setup');
    $response->assertRedirect('/login');
});

test('supervisors can access onboarding-setup workspace inertia page', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'is_supervisor' => true,
    ]);

    $response = $this->actingAs($user)->get('/admin/onboarding-setup');
    $response->assertOk();
});
