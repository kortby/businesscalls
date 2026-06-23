<?php

use App\Events\BookingStatusUpdated;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('guests are redirected from technician dashboard to login', function () {
    $response = $this->get(route('technician.dashboard'));
    $response->assertRedirect(route('login'));
});

test('non-technician users are redirected with an error when accessing technician dashboard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $response = $this->get(route('technician.dashboard'));
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('technicians can view their dashboard and see correct stats and efficiency score', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);

    // Create availability for today
    $dayOfWeek = now()->dayOfWeek;
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00', // 8 hours
        'is_active' => true,
    ]);

    // Create one completed booking and one travel time today
    Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'completed',
        'scheduled_start' => now()->setTime(10, 0),
        'travel_time' => 2.0, // 2 hours
    ]);

    $this->actingAs($user);

    // Act
    $response = $this->get(route('technician.dashboard'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('technician/Dashboard')
        ->has('employee')
        ->where('jCompleted', 1)
        ->where('tScheduled', 8)
        ->where('sumTravel', 2)
        // Lambda = j_completed / (t_scheduled + sum_travel) = 1 / (8 + 2) = 0.1
        ->where('performanceScore', 0.1)
    );
});

test('technician status transitions update timestamps and broadcast event', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'booked',
        'scheduled_start' => now()->setTime(10, 0),
    ]);

    $this->actingAs($user);

    // 1. Transition to en_route
    $response = $this->putJson("/api/bookings/{$booking->id}/status", [
        'status' => 'en_route',
    ]);

    $response->assertOk();
    $booking->refresh();
    expect($booking->status)->toBe('en_route');
    expect($booking->en_route_at)->not->toBeNull();

    Event::assertDispatched(BookingStatusUpdated::class, function ($event) use ($tenant, $booking) {
        return $event->tenantId === $tenant->id && $event->booking->id === $booking->id;
    });

    // 2. Transition to on_site (calculates travel time)
    // Pretend we traveled for 30 minutes
    $booking->en_route_at = now()->subMinutes(30);
    $booking->save();

    $response = $this->putJson("/api/bookings/{$booking->id}/status", [
        'status' => 'on_site',
    ]);

    $response->assertOk();
    $booking->refresh();
    expect($booking->status)->toBe('on_site');
    expect($booking->on_site_at)->not->toBeNull();
    // 30 mins / 60 = 0.5 hours (account for slight test execution delay)
    expect($booking->travel_time)->toEqualWithDelta(0.5, 0.05);

    // 3. Transition to completed with feedback & billing amount
    $response = $this->putJson("/api/bookings/{$booking->id}/status", [
        'status' => 'completed',
        'feedback' => 'Fixed the leak cleanly.',
        'billing_amount' => 150.00,
    ]);

    $response->assertOk();
    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->completed_at)->not->toBeNull();
    expect($booking->job_details)->toContain('Fixed the leak cleanly.');
    expect($booking->job_details)->toContain('$150.00');
});
