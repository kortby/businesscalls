<?php

use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view customers aggregated list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $employee = Employee::create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Eugene',
        'last_name' => 'Krabs',
        'phone' => '555-555-5555',
        'skills' => [],
        'notification_preference' => 'sms',
    ]);

    // Create a booking with customer phone
    Booking::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'customer_phone' => '+15551112222',
        'job_details' => 'Faucet replacement',
        'status' => 'booked',
        'scheduled_start' => now(),
    ]);

    // Create a call log with customer phone
    CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-crm-cust-1',
        'customer_phone' => '+15551112222',
        'status' => 'ended',
        'summary' => json_encode([
            'caller_name' => 'Eugene Krabs',
            'sentiment' => 'Neutral',
            'summary' => 'Krabs calls about plumbing',
        ]),
    ]);

    $response = $this->actingAs($user)->get(route('customers.index'));

    $response->assertStatus(200)
        ->assertSee('+15551112222')
        ->assertSee('Eugene Krabs');
});
