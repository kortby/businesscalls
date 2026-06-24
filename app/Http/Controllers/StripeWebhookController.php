<?php

namespace App\Http\Controllers;

use App\Events\DispatchUpdated;
use App\Models\AuditLog;
use App\Models\Tenant;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierController
{
    /**
     * Handle invoice payment succeeded.
     */
    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $priceId = $payload['data']['object']['lines']['data'][0]['price']['id'] ?? 'price_pro';
            $plan = 'pro';
            if (str_contains($priceId, 'enterprise')) {
                $plan = 'enterprise';
            } elseif (str_contains($priceId, 'basic')) {
                $plan = 'basic';
            }

            $tenant->plan = $plan;
            $settings = $tenant->settings ?? [];
            $settings['dispatch_locked'] = false;
            if ($plan === 'enterprise') {
                $settings['call_limit'] = 10000;
            } elseif ($plan === 'pro') {
                $settings['call_limit'] = 1000;
            } else {
                $settings['call_limit'] = 100;
            }
            $tenant->settings = $settings;
            $tenant->save();

            // Log compliance audit log
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => null, // webhook event (system action)
                'action' => 'plan_upgraded',
                'ip_address' => '127.0.0.1',
                'browser_agent' => 'Stripe Webhook',
                'payload' => [
                    'plan' => $plan,
                    'price_id' => $priceId,
                ],
            ]);

            // Broadcast real-time Reverb update to dashboard
            event(new DispatchUpdated($tenant->id, [
                'type' => 'success',
                'message' => 'Invoice payment succeeded. Subscription updated to '.ucfirst($plan).' Plan.',
            ]));
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed.
     */
    public function handleInvoicePaymentFailed(array $payload): Response
    {
        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $settings = $tenant->settings ?? [];
            $settings['dispatch_locked'] = true;
            $tenant->settings = $settings;
            $tenant->save();

            // Log audit log
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => 'payment_failed',
                'ip_address' => '127.0.0.1',
                'browser_agent' => 'Stripe Webhook',
                'payload' => [
                    'invoice_id' => $payload['data']['object']['id'] ?? null,
                    'amount_due' => $payload['data']['object']['amount_due'] ?? null,
                ],
            ]);

            event(new DispatchUpdated($tenant->id, [
                'type' => 'error',
                'message' => 'Billing Payment Failed! Dispatch Panel Locked.',
            ]));
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        // Execute Cashier's native subscription cancellation logic first
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $tenant->plan = 'free';
            $settings = $tenant->settings ?? [];
            $settings['dispatch_locked'] = false; // Reverting to free doesn't lock panel, but limits apply
            $settings['call_limit'] = 100;
            $tenant->settings = $settings;
            $tenant->save();

            // Log audit log
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => 'subscription_canceled',
                'ip_address' => '127.0.0.1',
                'browser_agent' => 'Stripe Webhook',
                'payload' => [
                    'subscription_id' => $payload['data']['object']['id'] ?? null,
                ],
            ]);

            event(new DispatchUpdated($tenant->id, [
                'type' => 'error',
                'message' => 'Subscription deleted. Reverted to Free tier.',
            ]));
        }

        return $response;
    }
}
