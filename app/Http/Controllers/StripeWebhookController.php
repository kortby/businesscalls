<?php

namespace App\Http\Controllers;

use App\Events\DispatchUpdated;
use App\Mail\AdminNewSubscriberNotificationMail;
use App\Mail\SubscriptionReceiptMail;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            $invoiceObj = $payload['data']['object'] ?? [];
            $priceId = $invoiceObj['lines']['data'][0]['price']['id'] ?? '';
            $proPriceId = config('cashier.pro_price_id') ?: env('STRIPE_PRO_PRICE_ID', 'price_pro');
            $enterprisePriceId = config('cashier.enterprise_price_id') ?: env('STRIPE_ENTERPRISE_PRICE_ID', 'price_enterprise');

            $plan = 'pro';
            if ($priceId === $enterprisePriceId || str_contains($priceId, 'enterprise')) {
                $plan = 'enterprise';
            } elseif ($priceId === $proPriceId || str_contains($priceId, 'pro')) {
                $plan = 'pro';
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

            // Extract invoice & customer details
            $rawAmount = $invoiceObj['amount_paid'] ?? $invoiceObj['total'] ?? null;
            $currency = strtoupper($invoiceObj['currency'] ?? 'usd');
            $formattedAmount = $rawAmount ? '$'.number_format($rawAmount / 100, 2).' '.$currency : null;
            $invoiceUrl = $invoiceObj['hosted_invoice_url'] ?? null;
            $invoicePdf = $invoiceObj['invoice_pdf'] ?? null;
            $invoiceNumber = $invoiceObj['number'] ?? null;

            $customerEmail = $invoiceObj['customer_email'] ?? $tenant->users()->first()?->email;
            $customerPhone = $tenant->getSetting('telephony_phone_number') ?? $tenant->employees()->first()?->phone;

            // 1. Send receipt & invoice confirmation email to Customer
            if ($customerEmail) {
                try {
                    Mail::to($customerEmail)->queue(
                        new SubscriptionReceiptMail(
                            tenant: $tenant,
                            plan: $plan,
                            amount: $formattedAmount,
                            invoiceUrl: $invoiceUrl,
                            invoicePdf: $invoicePdf,
                            invoiceNumber: $invoiceNumber
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to send customer subscription email: '.$e->getMessage());
                }
            }

            // 2. Send urgent notification email to Admin team to configure Twilio & Vapi ASAP
            $adminEmail = config('mail.admin_address', env('ADMIN_NOTIFICATION_EMAIL', 'admin@justmascot.com'));
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->queue(
                        new AdminNewSubscriberNotificationMail(
                            tenant: $tenant,
                            plan: $plan,
                            amount: $formattedAmount,
                            customerEmail: $customerEmail,
                            customerPhone: $customerPhone,
                            stripeCustomerId: $stripeId,
                            invoiceNumber: $invoiceNumber
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to send admin provisioning alert email: '.$e->getMessage());
                }
            }

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
                    'invoice_id' => $invoiceObj['id'] ?? null,
                    'invoice_number' => $invoiceNumber,
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
