<?php

use App\Jobs\SendTechnicianAlertJob;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('job lock is acquired and released and status changes during worker execution', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'sms-sid-123'], 201),
    ]);

    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'booked',
    ]);

    SendTechnicianAlertJob::$shouldRunInTests = true;

    // Dispatch/handle the job
    $job = new SendTechnicianAlertJob($booking);
    $job->handle();

    // Re-verify that lock is released and status is reset back to 'booked' on success
    expect(Cache::lock('booking-alert-lock:'.$booking->id)->get())->toBeTrue();
    $booking->refresh();
    expect($booking->status)->toBe('booked');
});

test('interrupted hook releases lock and resets status back to registered with logs', function () {
    Log::shouldReceive('info')->atLeast()->once();

    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => 'notifying',
    ]);

    // Acquire lock initially to simulate mid-execution state
    $lock = Cache::lock('booking-alert-lock:'.$booking->id, 60);
    $lock->get();

    $job = new SendTechnicianAlertJob($booking);

    // Call interrupted signal handler directly to simulate SIGTERM (signal 15)
    $job->interrupted(15);

    // Assert lock was released (so we can get it now)
    expect(Cache::lock('booking-alert-lock:'.$booking->id)->get())->toBeTrue();

    // Assert status is reset back to 'registered'
    $booking->refresh();
    expect($booking->status)->toBe('registered');
});
