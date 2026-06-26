<?php

use App\Events\DispatchUpdated;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\SubscriptionBuilder;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cashier.webhook.secret' => null]);
});

test('tenant can generate billing portal redirect URL', function () {
    $tenant = Tenant::factory()->create(['stripe_id' => 'cus_12345']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);

    // Mock billingPortalUrl native method on Tenant model using Mockery
    $tenantMock = Mockery::mock($tenant)->makePartial();
    $tenantMock->shouldReceive('billingPortalUrl')->andReturn('https://stripe.com/portal-redirect-url');
    $user->setRelation('tenant', $tenantMock);

    $response = $this->actingAs($user)->getJson(route('billing.portal'));

    $response->assertStatus(200)
        ->assertJson(['url' => 'https://stripe.com/portal-redirect-url']);
});

test('tenant can generate checkout session URL for pro plan', function () {
    $tenant = Tenant::factory()->create(['stripe_id' => 'cus_12345']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_supervisor' => true]);

    $tenantMock = Mockery::mock($tenant)->makePartial();

    $builderMock = Mockery::mock(SubscriptionBuilder::class);
    $checkoutSessionMock = (object) ['url' => 'https://stripe.com/checkout-session-url'];

    $builderMock->shouldReceive('checkout')->andReturn($checkoutSessionMock);

    $tenantMock->shouldReceive('newSubscription')
        ->with('default', env('STRIPE_PRO_PRICE_ID', 'price_pro'))
        ->andReturn($builderMock);

    $user->setRelation('tenant', $tenantMock);

    $response = $this->actingAs($user)->postJson(route('billing.checkout'), [
        'plan' => 'pro',
    ]);

    $response->assertStatus(200)
        ->assertJson(['url' => 'https://stripe.com/checkout-session-url']);

    // Assert audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'action' => 'checkout_initiated',
    ]);
});

test('webhook invoice.payment_succeeded updates plan and thresholds', function () {
    $tenant = Tenant::factory()->create([
        'stripe_id' => 'cus_test_webhook_123',
        'plan' => 'free',
        'settings' => ['dispatch_locked' => true],
    ]);

    $payload = [
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => 'cus_test_webhook_123',
                'lines' => [
                    'data' => [
                        [
                            'price' => [
                                'id' => 'price_pro_plan',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    Event::fake([DispatchUpdated::class]);

    $response = $this->postJson(route('cashier.webhook'), $payload);

    $response->assertStatus(200);

    $tenant->refresh();
    expect($tenant->plan)->toBe('pro');
    expect($tenant->settings['dispatch_locked'])->toBeFalse();
    expect($tenant->settings['call_limit'])->toBe(1000);

    // Verify audit log
    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'plan_upgraded',
    ]);

    Event::assertDispatched(DispatchUpdated::class);
});

test('webhook invoice.payment_failed locks dispatch panel', function () {
    $tenant = Tenant::factory()->create([
        'stripe_id' => 'cus_test_webhook_123',
        'plan' => 'pro',
        'settings' => ['dispatch_locked' => false],
    ]);

    $payload = [
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'customer' => 'cus_test_webhook_123',
                'id' => 'in_12345',
                'amount_due' => 7900,
            ],
        ],
    ];

    Event::fake([DispatchUpdated::class]);

    $response = $this->postJson(route('cashier.webhook'), $payload);

    $response->assertStatus(200);

    $tenant->refresh();
    expect($tenant->settings['dispatch_locked'])->toBeTrue();

    // Verify audit log
    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'payment_failed',
    ]);

    Event::assertDispatched(DispatchUpdated::class);
});

test('webhook customer.subscription.deleted reverts to free tier', function () {
    $tenant = Tenant::factory()->create([
        'stripe_id' => 'cus_test_webhook_123',
        'plan' => 'pro',
        'settings' => ['dispatch_locked' => false],
    ]);

    $payload = [
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'customer' => 'cus_test_webhook_123',
                'id' => 'sub_12345',
            ],
        ],
    ];

    // Setup local subscription mapping to prevent Cashier parent method from failing
    DB::table('subscriptions')->insert([
        'tenant_id' => $tenant->id,
        'type' => 'default',
        'stripe_id' => 'sub_12345',
        'stripe_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Event::fake([DispatchUpdated::class]);

    $response = $this->postJson(route('cashier.webhook'), $payload);

    $response->assertStatus(200);

    $tenant->refresh();
    expect($tenant->plan)->toBe('free');
    expect($tenant->settings['dispatch_locked'])->toBeFalse();
    expect($tenant->settings['call_limit'])->toBe(100);

    // Verify audit log
    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'action' => 'subscription_canceled',
    ]);

    Event::assertDispatched(DispatchUpdated::class);
});
