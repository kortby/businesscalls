<?php

use App\Events\TechnicianLocationUpdated;
use App\Jobs\SendOnMyWayAlertJob;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\DraftTask;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DiagnosticValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('verify technical triage diagnostic coefficient calculation', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'triage_notes' => 'Appliance leaking water actively.',
        'appliance_brand' => 'Whirlpool',
        'appliance_age' => 5,
        'urgency_markers' => ['water_leak', 'active_flood'],
    ]);

    $service = app(DiagnosticValidationService::class);
    $score = $service->calculateAndSave($booking);

    // Psi_triage should be 1.0 (all 4 components are present)
    expect($score)->toEqual(1.0);

    // Assert audit log was recorded
    $log = AuditLog::where('tenant_id', $tenant->id)
        ->where('action', 'triage_index_calculation')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->payload['psi_triage'])->toEqual(1.0);
    expect($log->payload['booking_id'])->toBe($booking->id);
});

test('verify status transition to en_route triggers SendOnMyWayAlertJob', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => 'booked',
    ]);

    $booking->update(['status' => 'en_route']);

    Queue::assertPushed(SendOnMyWayAlertJob::class, function ($job) use ($booking) {
        return $job->booking->id === $booking->id;
    });
});

test('verify public route track booking hash and location updates', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $hash = md5('test-booking-hash');
    $booking = Booking::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'booking_hash' => $hash,
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);

    // Test tracking route works publicly without authentication
    $response = $this->get("/track/{$hash}");
    if ($response->status() !== 200) {
        dd($response->getContent());
    }
    $response->assertOk();

    // Test coordinates updates endpoint with auth
    $locationPayload = [
        'latitude' => 37.7801,
        'longitude' => -122.4222,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/bookings/{$booking->id}/location", $locationPayload);

    $response->assertOk();

    $employee->refresh();
    expect((float) $employee->latitude)->toBe(37.7801);
    expect((float) $employee->longitude)->toBe(-122.4222);

    Event::assertDispatched(TechnicianLocationUpdated::class, function ($event) use ($booking) {
        return $event->booking->id === $booking->id
            && $event->latitude === 37.7801
            && $event->longitude === -122.4222;
    });
});

test('verify call analysis webhook extracts metrics and generates draft tasks', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callId = 'call-test-analysis-123';
    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => $callId,
        'transcript' => 'We need to order replacement parts for the Whirlpool refrigerator.',
        'summary' => 'Customer called to request HVAC check-up. The diagnostic requires us to order parts.',
        'sentiment' => 'satisfied',
        'category' => 'refrigerator_repair',
        'scorecard' => [
            'greeting' => true,
            'polite' => true,
        ],
    ];

    $response = $this->postJson('/api/webhooks/call-analysis', $payload);
    $response->assertOk();

    // Verify CallLog updated
    $callLog = CallLog::where('call_id', $callId)->first();
    expect($callLog)->not->toBeNull();
    expect($callLog->user_sentiment)->toBe('satisfied');
    expect($callLog->job_category)->toBe('refrigerator_repair');
    expect($callLog->performance_scorecard)->toBeArray();

    // Verify DraftTask was created
    $task = DraftTask::where('call_id', $callId)
        ->where('task_type', 'order_parts')
        ->first();

    expect($task)->not->toBeNull();
    expect($task->status)->toBe('pending');
});
