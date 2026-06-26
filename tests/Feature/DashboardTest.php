<?php

use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('all navigation sidebar links resolve and load successfully', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);
    $this->actingAs($user);

    $routes = [
        'dashboard',
        'bookings.index',
        'availabilities.index',
        'employees.index',
        'customers.index',
        'jobs.index',
        'conversations.index',
        'admin.dispatch-map',
        'admin.call-monitor',
        'admin.supervisor-hud',
        'admin.status-hud',
        'admin.preflight',
        'admin.diagnostics',
        'admin.sla-diagnostics',
        'admin.health',
        'admin.callflow',
        'admin.saas-profit',
        'admin.onboarding',
        'admin.onboarding-board',
        'admin.streak-hub',
        'admin.csat-feedback',
        'admin.achievements',
        'admin.leaderboard',
        'admin.billing-hub',
        'admin.loyalty',
        'admin.audit-logs',
        'admin.mascot-shop',
        'admin.integrations',
        'admin.experiments',
        'docs',
    ];

    foreach ($routes as $route) {
        $response = $this->get(route($route));
        expect($response->status())->toBeIn([200, 302]);
    }
});

test('dashboard numbers are scoped to the authenticated user tenant', function () {
    $tenant1 = Tenant::factory()->create(['is_test_mode' => false]);
    $user1 = User::factory()->create(['tenant_id' => $tenant1->id, 'is_supervisor' => true]);

    $tenant2 = Tenant::factory()->create(['is_test_mode' => false]);
    $user2 = User::factory()->create(['tenant_id' => $tenant2->id, 'is_supervisor' => true]);

    // Create an employee for tenant 1
    Employee::factory()->create(['tenant_id' => $tenant1->id]);

    // Create a call log for tenant 1
    CallLog::create([
        'tenant_id' => $tenant1->id,
        'call_id' => 'call-t1',
        'status' => 'completed',
        'customer_phone' => '555-0101',
        'is_test_mode' => false,
    ]);

    // Create a call log for tenant 2
    CallLog::create([
        'tenant_id' => $tenant2->id,
        'call_id' => 'call-t2',
        'status' => 'completed',
        'customer_phone' => '555-0102',
        'is_test_mode' => false,
    ]);

    $this->actingAs($user1);
    $response = $this->get(route('dashboard'));
    $response->assertOk();

    // Verify page props contain only tenant 1 stats
    $inertiaData = $response->original->getData()['page']['props'];
    expect($inertiaData['totalCallsCount'])->toBe(1);
});
