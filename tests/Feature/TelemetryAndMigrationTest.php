<?php

use App\Jobs\ProcessLatencyDriftJob;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $configDir = config_path('deploy');
    if (! File::exists($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }
    File::put("{$configDir}/environments.yaml", <<<'YAML'
dev:
  slug: "dev"
  voice_assistant_id: "vapi-dev-id"
  telephony_provider: "vapi"
  telephony_phone_number: "+15550001111"
  telephony_phone_number_id: "dev-phone-sid"
  webhook_url: "https://dev.businesscalls.com/webhook"

uat:
  slug: "uat"
  voice_assistant_id: "vapi-uat-id"
  telephony_provider: "vapi"
  telephony_phone_number: "+15550002222"
  telephony_phone_number_id: "uat-phone-sid"
  webhook_url: "https://uat.businesscalls.com/webhook"

prod:
  slug: "prod"
  voice_assistant_id: "vapi-prod-id"
  telephony_provider: "vapi"
  telephony_phone_number: "+15550003333"
  telephony_phone_number_id: "prod-phone-sid"
  webhook_url: "https://api.businesscalls.com/webhook"
YAML
    );
});

test('ProcessLatencyDriftJob computes drift index correctly and stores it', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $callLog = CallLog::create([
        'call_id' => 'test-call-123',
        'status' => 'ended',
        'customer_phone' => '123-456-7890',
    ]);

    // Simulated turns:
    // Turn 1: diff = 2000 - 1000 = 1000ms
    // Turn 2: diff = 4600 - 3000 = 1600ms
    // Avg diff = (1000 + 1600) / 2 = 1300ms
    // Drift = 1300 - 600 (target) = 700ms
    $telemetry = [
        'turns' => [
            ['audio_in_ms' => 1000.0, 'audio_out_ms' => 2000.0],
            ['audio_in_ms' => 3000.0, 'audio_out_ms' => 4600.0],
        ],
    ];

    ProcessLatencyDriftJob::dispatchSync($tenant->id, 'test-call-123', $telemetry);

    expect($callLog->fresh()->latency_drift)->toEqual(700.0);
});

test('ProcessLatencyDriftJob triggers alert escalation after 3 consecutive high-drift calls', function () {
    Http::fake();

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Create 3 call logs
    $call1 = CallLog::create(['call_id' => 'call-1', 'status' => 'ended', 'customer_phone' => '123-456-7890']);
    $call2 = CallLog::create(['call_id' => 'call-2', 'status' => 'ended', 'customer_phone' => '123-456-7890']);
    $call3 = CallLog::create(['call_id' => 'call-3', 'status' => 'ended', 'customer_phone' => '123-456-7890']);

    // High drift turns (Avg diff = 2000ms, Drift = 2000 - 600 = 1400ms > 1200ms)
    $telemetry = [
        'turns' => [
            ['audio_in_ms' => 1000.0, 'audio_out_ms' => 3000.0],
        ],
    ];

    // Dispatch for call 1 & 2
    ProcessLatencyDriftJob::dispatchSync($tenant->id, 'call-1', $telemetry);
    ProcessLatencyDriftJob::dispatchSync($tenant->id, 'call-2', $telemetry);

    // Verify no alert yet
    $incidentLogs = AuditLog::where('tenant_id', $tenant->id)
        ->where('action', 'high_priority_incident')
        ->get();
    expect($incidentLogs)->toHaveCount(0);

    // Dispatch for call 3 (exceeds threshold across 3 consecutive calls)
    ProcessLatencyDriftJob::dispatchSync($tenant->id, 'call-3', $telemetry);

    // Verify warning webhook is called
    Http::assertSent(function ($request) use ($tenant) {
        return $request->url() === 'https://api.vapi.ai/latency-warning'
            && $request['tenant_id'] === $tenant->id
            && $request['calculated_drift_ms'] === 1400.0;
    });

    // Verify incident report logged in AuditLog
    $incidentLogs = AuditLog::where('tenant_id', $tenant->id)
        ->where('action', 'high_priority_incident')
        ->get();
    expect($incidentLogs)->toHaveCount(1)
        ->and($incidentLogs->first()->payload['alert_type'])->toEqual('high_latency_drift');
});

test('BookingObserver dual-writes job_details and booking_notes to prevent downtime', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    // Create via job_details -> replicates to booking_notes
    $booking1 = Booking::create([
        'employee_id' => $employee->id,
        'customer_phone' => '123-456-7890',
        'job_details' => 'Inspect furnace',
        'status' => 'booked',
        'scheduled_start' => now(),
    ]);

    expect($booking1->booking_notes)->toEqual('Inspect furnace');

    // Create via booking_notes -> replicates to job_details
    $booking2 = Booking::create([
        'employee_id' => $employee->id,
        'customer_phone' => '123-456-7890',
        'booking_notes' => 'Fix leaking water heater',
        'status' => 'booked',
        'scheduled_start' => now(),
    ]);

    expect($booking2->job_details)->toEqual('Fix leaking water heater');

    // Update job_details -> replicates to booking_notes
    $booking1->update(['job_details' => 'Furnace cleaned & inspected']);
    expect($booking1->fresh()->booking_notes)->toEqual('Furnace cleaned & inspected');
});

test('Zero-downtime contract migration drops deprecated job_details column successfully', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    // Verify column exists initially (expand migration ran in setup)
    expect(Schema::hasColumn('bookings', 'job_details'))->toBeTrue()
        ->and(Schema::hasColumn('bookings', 'booking_notes'))->toBeTrue();

    // Run the contract migration up using environment override
    putenv('RUN_CONTRACT_MIGRATIONS=true');
    $migrationClass = require database_path('migrations/2026_06_24_030000_contract_bookings_table_zero_downtime.php');
    $migrationClass->up();

    // Verify job_details has been dropped
    expect(Schema::hasColumn('bookings', 'job_details'))->toBeFalse()
        ->and(Schema::hasColumn('bookings', 'booking_notes'))->toBeTrue();

    // Rollback the contract migration
    $migrationClass->down();
    expect(Schema::hasColumn('bookings', 'job_details'))->toBeTrue();

    putenv('RUN_CONTRACT_MIGRATIONS'); // Reset env
});

test('Mock deployment promo, queue, and reverb restart hooks run successfully', function () {
    $this->artisan('deploy:promote', ['environment' => 'uat'])
        ->assertExitCode(0);

    $this->artisan('queue:restart')
        ->assertExitCode(0);

    $this->artisan('reverb:restart')
        ->assertExitCode(0);
});
