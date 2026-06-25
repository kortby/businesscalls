<?php

use App\Jobs\SendSmsConfirmationJob;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('SendSmsConfirmationJob sends Vapi SMS payload with tracking link when active booking is found', function () {
    Http::fake();

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-sms-conf-111',
        'status' => 'ended',
        'customer_phone' => '+15550001234',
    ]);

    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15550001234',
        'job_details' => 'AC repair service',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
    ]);

    Cache::put('call_booking_map:call-sms-conf-111', $booking->id, 600);

    config(['services.telephony.provider' => 'vapi']);

    SendSmsConfirmationJob::dispatchSync($callLog);

    Http::assertSent(function ($request) use ($booking) {
        return str_contains($request->url(), 'api.vapi.ai/sms')
            && $request['to'] === '+15550001234'
            && str_contains($request['message'], 'Track your technician here:')
            && str_contains($request['message'], "booking_id={$booking->id}");
    });
});

test('SendSmsConfirmationJob sends Retell SMS payload with tracking link', function () {
    Http::fake();

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-sms-conf-222',
        'status' => 'ended',
        'customer_phone' => '+15550005678',
    ]);

    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15550005678',
        'job_details' => 'Electrical emergency short',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
    ]);

    Cache::put('call_booking_map:call-sms-conf-222', $booking->id, 600);

    config(['services.telephony.provider' => 'retell']);

    SendSmsConfirmationJob::dispatchSync($callLog);

    Http::assertSent(function ($request) use ($booking) {
        return str_contains($request->url(), 'api.retellai.com/v2/sms')
            && $request['to'] === '+15550005678'
            && str_contains($request['text'], 'Track your technician here:')
            && str_contains($request['text'], "booking_id={$booking->id}");
    });
});

test('DispatchWebhookController triggers emergency steering safety patch to Retell', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'secret_key' => null,
        'client_id' => null,
    ]);

    config(['services.telephony.provider' => 'retell']);

    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-emergency-steering-999',
        'service_type' => 'gas leak detection',
        'customer_phone' => '+15557778888',
        'requested_time' => '2026-06-25 10:00:00',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.retellai.com/v2/calls/call-emergency-steering-999')
            && $request->method() === 'PATCH'
            && $request['assistant_overrides']['voice_id'] === '11labs-authoritative'
            && str_contains($request['assistant_overrides']['prompt'], 'EMERGENCY SCRIPT:')
            && $request['assistant_overrides']['stop_speaking_threshold'] === 2.0;
    });
});

test('DispatchWebhookController triggers emergency steering safety patch to Vapi', function () {
    Http::fake();

    $tenant = Tenant::factory()->create([
        'secret_key' => null,
        'client_id' => null,
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $payload = [
        'tenant_id' => $tenant->id,
        'call_id' => 'call-emergency-steering-888',
        'service_type' => 'electrical short circuit',
        'customer_phone' => '+15557778888',
        'requested_time' => '2026-06-25 10:00:00',
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.vapi.ai/call/call-emergency-steering-888')
            && $request->method() === 'PATCH'
            && $request['assistantOverrides']['voice']['voiceId'] === 'pNInz6obpgfrDuZJe63m'
            && str_contains($request['assistantOverrides']['model']['messages'][0]['content'], 'EMERGENCY SCRIPT:')
            && $request['assistantOverrides']['stopSpeakingThreshold'] === 2.0;
    });
});

test('admin/csat-feedback dashboard returns correctly calculated CSAT index and SLA fields', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);

    // Call 1: Resolved with booking, CSAT score = 100% (5/5)
    $call1 = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-csat-test-1',
        'customer_phone' => '+15551239999',
        'status' => 'ended',
        'csat_score' => 100.0,
        'latency' => 500,
    ]);

    $booking1 = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15551239999',
        'job_details' => 'AC Tune-up',
        'status' => 'booked',
        'scheduled_start' => now()->addDay(),
    ]);
    Cache::put('call_booking_map:call-csat-test-1', $booking1->id, 600);

    // Call 2: Unresolved (no booking), CSAT score = 60% (3/5)
    $call2 = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-csat-test-2',
        'customer_phone' => '+15551238888',
        'status' => 'ended',
        'csat_score' => 60.0,
        'latency' => 1000,
    ]);

    $response = $this->actingAs($user)->get(route('admin.csat-feedback'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/CsatFeedback')
            ->where('weeklyAvgPhi', 0.467)
            ->where('isProcessing', false)
            ->where('hasRecentError', false)
        );
});
