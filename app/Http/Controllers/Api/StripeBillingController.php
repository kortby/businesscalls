<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StripeBillingController extends Controller
{
    /**
     * Show the billing settings view.
     */
    public function index(Request $request): InertiaResponse
    {
        $tenant = $request->user()->tenant;

        $isSubscribed = $tenant->subscribed('default') || ($tenant->is_test_mode && in_array($tenant->plan, ['pro', 'enterprise']));

        return Inertia::render('settings/Billing', [
            'tenant' => $tenant,
            'stripeKey' => config('cashier.key'),
            'pmType' => $tenant->pm_type,
            'pmLastFour' => $tenant->pm_last_four,
            'isSubscribed' => $isSubscribed,
            'plan' => $tenant->plan,
        ]);
    }

    /**
     * Generate a Stripe Customer Portal redirect URL.
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'No active tenant found.'], 404);
        }

        // Apply Tenant database context
        TenantScope::setTenantId($tenant->id);

        if ($tenant->is_test_mode) {
            return response()->json(['url' => route('dashboard').'?mock_portal=true']);
        }

        try {
            if (! $tenant->stripe_id) {
                $tenant->createAsStripeCustomer();
            }

            // Generate Stripe hosted billing portal redirect URL
            $url = $tenant->billingPortalUrl(route('dashboard'));

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('Stripe billing portal error: '.$e->getMessage());

            return response()->json(['error' => 'Failed to connect to Stripe Billing Portal. Please check your STRIPE_SECRET key.'], 500);
        }
    }

    /**
     * Generate a Stripe Checkout Session redirect URL for plan upgrades.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'No active tenant found.'], 404);
        }

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:pro,enterprise'],
        ]);

        $plan = $validated['plan'];

        // Define price IDs - fallbacks allowed for local testing/mocking
        $priceId = $plan === 'enterprise'
            ? env('STRIPE_ENTERPRISE_PRICE_ID', 'price_enterprise')
            : env('STRIPE_PRO_PRICE_ID', 'price_pro');

        TenantScope::setTenantId($tenant->id);

        if ($tenant->is_test_mode) {
            // Mock subscription upgrade instantly in test mode!
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

            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'checkout_initiated_test',
                'ip_address' => $request->ip(),
                'browser_agent' => $request->userAgent(),
                'payload' => [
                    'plan' => $plan,
                    'price_id' => $priceId,
                    'test_mode' => true,
                ],
            ]);

            return response()->json(['url' => route('dashboard').'?checkout=success&test_mode=true']);
        }

        try {
            if (! $tenant->stripe_id) {
                $tenant->createAsStripeCustomer();
            }

            // Generate Checkout session URL
            $checkout = $tenant->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('dashboard').'?checkout=success',
                    'cancel_url' => route('settings.billing.index').'?checkout=cancel',
                ]);

            // Log compliance audit log for checkout intent
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'checkout_initiated',
                'ip_address' => $request->ip(),
                'browser_agent' => $request->userAgent(),
                'payload' => [
                    'plan' => $plan,
                    'price_id' => $priceId,
                ],
            ]);

            return response()->json(['url' => $checkout->url]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout session error: '.$e->getMessage());

            return response()->json(['error' => 'Failed to connect to Stripe Gateway. Please check your STRIPE_SECRET key.'], 500);
        }
    }
}
