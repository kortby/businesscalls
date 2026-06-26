<?php

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
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
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
