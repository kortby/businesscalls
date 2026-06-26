<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\MaintenanceAgreement;
use App\Models\OutboundCampaign;
use App\Models\PaymentTransaction;
use App\Models\Pricebook;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RouteOptimizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('pricebook dynamic fee quote tool lookup finds category diagnostic fee', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    TenantScope::setTenantId($tenant->id);

    Pricebook::create([
        'tenant_id' => $tenant->id,
        'item_code' => 'compressor_replace',
        'description' => 'Compressor replacement service',
        'flat_rate_price' => 150.00,
        'category' => 'HVAC',
        'diagnostic_required' => true,
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'function_name' => 'get_diagnostic_fee',
        'message' => [
            'type' => 'tool-calls',
            'toolCalls' => [
                [
                    'id' => 'call-id-abc',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_diagnostic_fee',
                        'arguments' => [
                            'service_type' => 'HVAC',
                            'problem' => 'compressor_replace',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/webhooks/dispatch', $payload);

    $response->assertOk();
    expect($response->json('results.0.result.diagnostic_fee'))->toEqual(150.00);
    expect($response->json('results.0.result.diagnostic_required'))->toBeTrue();
});

test('stripe payment intent authorization is confirmed before booking if diagnostic fee applies', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    TenantScope::setTenantId($tenant->id);

    // Setup Pricebook requiring diagnostic
    Pricebook::create([
        'tenant_id' => $tenant->id,
        'item_code' => 'HVAC_diag',
        'flat_rate_price' => 150.00,
        'category' => 'HVAC',
        'diagnostic_required' => true,
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'skills' => ['HVAC'],
    ]);

    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => Carbon::parse('2026-06-25 10:00:00')->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    $payload = [
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551112222',
        'service_type' => 'HVAC',
        'requested_time' => '2026-06-25 10:00:00',
        'call_id' => 'call-test-pay-confirm',
    ];

    // Attempt booking WITHOUT a successful PaymentTransaction
    $response = $this->postJson('/api/webhooks/dispatch', array_merge($payload, ['event_id' => 'evt_1']));
    $response->assertStatus(402); // Payment required
    expect(Booking::count())->toBe(0);

    // Seed successful payment transaction
    PaymentTransaction::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-test-pay-confirm',
        'amount' => 150.00,
        'status' => 'success',
    ]);

    // Retry booking
    $response2 = $this->postJson('/api/webhooks/dispatch', array_merge($payload, ['event_id' => 'evt_2']));
    $response2->assertOk();
    expect(Booking::count())->toBe(1);
});

test('ProcessMaintenanceAgreements aggregates due agreements and triggers campaign under capacity gate', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['secret_key' => null]);
    TenantScope::setTenantId($tenant->id);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'John PM',
        'phone' => '+15559876',
    ]);

    // Due PM agreement
    MaintenanceAgreement::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'system_type' => 'HVAC',
        'last_service_date' => Carbon::now()->subMonths(12),
        'next_service_due' => Carbon::now()->subDays(5),
        'status' => 'active',
    ]);

    // Setup active tech capacity shift
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => Carbon::now()->dayOfWeek,
        'is_active' => true,
    ]);

    // Execute PM scan command
    Artisan::call('app:process-maintenance-agreements', ['--tenant' => $tenant->id]);

    // Verify campaign is created
    $this->assertDatabaseHas('outbound_campaigns', [
        'tenant_id' => $tenant->id,
        'target_group' => 'Proactive Preventative Maintenance',
    ]);

    // Verify recipient aggregate
    $this->assertDatabaseHas('campaign_recipients', [
        'phone_number' => '+15559876',
        'name' => 'John PM',
    ]);
});

test('ProcessMaintenanceAgreements respects capacity gate and skips run when overbooked', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['secret_key' => null]);
    TenantScope::setTenantId($tenant->id);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'name' => 'John PM Over',
        'phone' => '+15557777',
    ]);

    // Due PM agreement
    MaintenanceAgreement::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'system_type' => 'HVAC',
        'last_service_date' => Carbon::now()->subMonths(12),
        'next_service_due' => Carbon::now()->subDays(5),
        'status' => 'active',
    ]);

    // Setup 1 tech availability shift (meaning capacity is 2 bookings max)
    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);
    Availability::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'day_of_week' => Carbon::now()->dayOfWeek,
        'is_active' => true,
    ]);

    // Overbook technician (create 3 active bookings this week)
    $startOfWeek = Carbon::now()->startOfWeek();
    for ($i = 0; $i < 3; $i++) {
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'scheduled_start' => $startOfWeek->copy()->addHours(9 + $i),
            'status' => 'booked',
        ]);
    }

    // Execute command
    Artisan::call('app:process-maintenance-agreements', ['--tenant' => $tenant->id]);

    // Verify campaign is NOT created (skipped due to capacity gate)
    expect(OutboundCampaign::count())->toBe(0);
});

test('RouteOptimizerService Haversine formula scores density correctly prioritizing nearby technicians', function () {
    $tenant = Tenant::factory()->create(['secret_key' => null]);
    TenantScope::setTenantId($tenant->id);

    // Nearby Tech (latitude offset ~0.01 degree)
    $tech1 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);

    // Far Away Tech (latitude offset ~0.5 degree)
    $tech2 = Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'latitude' => 38.2749,
        'longitude' => -122.4194,
    ]);

    $optimizer = app(RouteOptimizerService::class);
    $time = Carbon::parse('2026-06-25 10:00:00');

    $score1 = $optimizer->calculateDensityScore($tech1, $time, 37.7750, -122.4195);
    $score2 = $optimizer->calculateDensityScore($tech2, $time, 37.7750, -122.4195);

    expect($score1)->toBeGreaterThan($score2);
});

test('authenticated administrators can access SaaS profit HUD dashboard', function () {
    $tenant = Tenant::factory()->create();
    TenantScope::setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
        'is_supervisor' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.saas-profit'));

    $response->assertOk();
});
