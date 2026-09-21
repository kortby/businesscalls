<?php

use App\Events\DispatchUpdated;
use App\Mail\AdminNewSubscriberNotificationMail;
use App\Mail\SubscriptionReceiptMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
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

test('webhook invoice.payment_succeeded updates plan, thresholds, and sends customer and admin emails', function () {
    Mail::fake();

    $tenant = Tenant::factory()->create([
        'stripe_id' => 'cus_test_webhook_123',
        'name' => 'Apex Air & Heating',
        'plan' => 'free',
        'settings' => ['dispatch_locked' => true, 'telephony_phone_number' => '+16195551234'],
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'client@example.com',
        'is_supervisor' => true,
    ]);

    $payload = [
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'id' => 'in_1234567890',
                'number' => 'INV-2026-001',
                'customer' => 'cus_test_webhook_123',
                'customer_email' => 'client@example.com',
                'amount_paid' => 7900,
                'currency' => 'usd',
                'hosted_invoice_url' => 'https://invoice.stripe.com/i/acct_123/invst_123',
                'invoice_pdf' => 'https://pay.stripe.com/invoice/acct_123/invst_123/pdf',
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

    // Assert customer receipt email was queued
    Mail::assertQueued(SubscriptionReceiptMail::class, function ($mail) use ($tenant) {
        return $mail->tenant->id === $tenant->id
            && $mail->plan === 'pro'
            && $mail->amount === '$79.00 USD'
            && $mail->invoiceUrl === 'https://invoice.stripe.com/i/acct_123/invst_123'
            && $mail->invoicePdf === 'https://pay.stripe.com/invoice/acct_123/invst_123/pdf'
            && $mail->invoiceNumber === 'INV-2026-001'
            && $mail->hasTo('client@example.com');
    });

    // Assert urgent admin provisioning email was queued
    Mail::assertQueued(AdminNewSubscriberNotificationMail::class, function ($mail) use ($tenant) {
        return $mail->tenant->id === $tenant->id
            && $mail->plan === 'pro'
            && $mail->customerEmail === 'client@example.com'
            && $mail->customerPhone === '+16195551234'
            && $mail->stripeCustomerId === 'cus_test_webhook_123'
            && $mail->invoiceNumber === 'INV-2026-001';
    });
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

test('subscription receipt mail renders properly with invoice links', function () {
    $tenant = Tenant::factory()->create(['name' => 'Acme Plumbing Co']);

    $mailable = new SubscriptionReceiptMail(
        tenant: $tenant,
        plan: 'enterprise',
        amount: '$199.00 USD',
        invoiceUrl: 'https://invoice.stripe.com/test-invoice-url',
        invoicePdf: 'https://pay.stripe.com/test-invoice.pdf',
        invoiceNumber: 'INV-2026-999'
    );

    $mailable->assertHasSubject('Your JustMascot Subscription Receipt & Invoice (#INV-2026-999)');
    $mailable->assertSeeInHtml('Acme Plumbing Co');
    $mailable->assertSeeInHtml('Enterprise Plan');
    $mailable->assertSeeInHtml('$199.00 USD');
    $mailable->assertSeeInHtml('https://invoice.stripe.com/test-invoice-url');
    $mailable->assertSeeInHtml('https://pay.stripe.com/test-invoice.pdf');
});

test('admin new subscriber notification mail renders with provisioning checklist', function () {
    $tenant = Tenant::factory()->create(['name' => 'Fast Electricians LLC']);

    $mailable = new AdminNewSubscriberNotificationMail(
        tenant: $tenant,
        plan: 'pro',
        amount: '$79.00 USD',
        customerEmail: 'fast@electric.com',
        customerPhone: '+16195559876',
        stripeCustomerId: 'cus_live_99999',
        invoiceNumber: 'INV-2026-002'
    );

    $mailable->assertHasSubject('🚨 URGENT: New Paid Subscriber - Configure Twilio & Vapi for Fast Electricians LLC (Pro Plan)');
    $mailable->assertSeeInHtml('Fast Electricians LLC');
    $mailable->assertSeeInHtml('fast@electric.com');
    $mailable->assertSeeInHtml('+16195559876');
    $mailable->assertSeeInHtml('cus_live_99999');
    $mailable->assertSeeInHtml('Twilio');
    $mailable->assertSeeInHtml('Vapi');
});
