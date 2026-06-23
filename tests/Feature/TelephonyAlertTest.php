<?php

use App\Jobs\SendTechnicianAlertJob;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TelephonyProvisioningService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
    SendTechnicianAlertJob::$shouldRunInTests = true;
});

afterEach(function () {
    SendTechnicianAlertJob::$shouldRunInTests = false;
});

test('telephony provisioning service purchases a number successfully', function () {
    Http::fake([
        'https://api.vapi.ai/phone-number' => Http::response([
            'id' => 'vapi-phone-sid-999',
            'number' => '+13025550199',
        ], 200),
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $tenant = Tenant::factory()->create();
    $service = new TelephonyProvisioningService;

    $result = $service->purchasePhoneNumber($tenant, '302');

    expect($result)->toBe([
        'phone_number' => '+13025550199',
        'phone_number_id' => 'vapi-phone-sid-999',
    ]);

    // Check DB
    $tenant->refresh();
    expect($tenant->getSetting('telephony_phone_number_id'))->toBe('vapi-phone-sid-999')
        ->and($tenant->getSetting('telephony_phone_number'))->toBe('+13025550199');
});

test('telephony provisioning service handles Retell API correctly', function () {
    Http::fake([
        'https://api.retellai.com/buy-phone-number' => Http::response([
            'phone_number_id' => 'retell-phone-sid-888',
            'phone_number' => '+13025550188',
        ], 200),
    ]);

    config(['services.telephony.provider' => 'retell']);

    $tenant = Tenant::factory()->create();
    $service = new TelephonyProvisioningService;

    $result = $service->purchasePhoneNumber($tenant, '302');

    expect($result)->toBe([
        'phone_number' => '+13025550188',
        'phone_number_id' => 'retell-phone-sid-888',
    ]);

    // Check DB
    $tenant->refresh();
    expect($tenant->getSetting('telephony_phone_number_id'))->toBe('retell-phone-sid-888')
        ->and($tenant->getSetting('telephony_phone_number'))->toBe('+13025550188');
});

test('telephony provisioning service throws billing exceptions', function () {
    Http::fake([
        'https://api.vapi.ai/phone-number' => Http::response([
            'error' => 'Your account has insufficient balance to complete this phone purchase.',
        ], 400),
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $tenant = Tenant::factory()->create();
    $service = new TelephonyProvisioningService;

    expect(fn () => $service->purchasePhoneNumber($tenant, '302'))
        ->toThrow(Exception::class, 'Telephony billing error');
});

test('send technician alert job sends Twilio SMS notifications', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'sms-sid-123'], 201),
    ]);

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Bob',
        'last_name' => 'Tech',
        'phone' => '+15550002222',
        'skills' => ['plumbing'],
        'notification_preference' => 'sms',
    ]);

    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15559876543',
        'job_details' => 'Repair leaking toilet',
        'scheduled_start' => now()->addDay(),
        'status' => 'booked',
    ]);

    $job = new SendTechnicianAlertJob($booking);
    $job->handle();

    Http::assertSent(function (Request $request) use ($employee) {
        return str_contains($request->url(), 'twilio.com') &&
               $request['To'] === $employee->phone &&
               str_contains($request['Body'], 'Bob');
    });
});

test('send technician alert job triggers voice calls', function () {
    Http::fake([
        'https://api.vapi.ai/call' => Http::response(['id' => 'call-sid-999'], 201),
    ]);

    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Jane',
        'last_name' => 'HVAC',
        'phone' => '+15550003333',
        'skills' => ['hvac'],
        'notification_preference' => 'voice',
    ]);

    $booking = Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15559876543',
        'job_details' => 'Inspect AC compressor',
        'scheduled_start' => now()->addDay(),
        'status' => 'booked',
    ]);

    config(['services.telephony.provider' => 'vapi']);

    $job = new SendTechnicianAlertJob($booking);
    $job->handle();

    Http::assertSent(function (Request $request) use ($employee) {
        return $request->url() === 'https://api.vapi.ai/call' &&
               $request['customer']['number'] === $employee->phone &&
               $request['assistantOverrides']['variableValues']['first_name'] === 'Jane';
    });
});
