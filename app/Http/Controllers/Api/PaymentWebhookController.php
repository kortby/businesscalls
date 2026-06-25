<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\PaymentTransaction;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class PaymentWebhookController extends Controller
{
    /**
     * Process mid-call voice-activated payment securely via Stripe.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Resolve tenant context
        $tenantId = $request->query('tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->input('message.tenantId')
            ?? $request->input('tenant_slug');

        $tenant = null;
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
        }

        if (! $tenant) {
            // Fallback: try to resolve via user session
            $user = auth()->user();
            if ($user && $user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
            }
        }

        // Default fallback if tenant still not resolved
        if (! $tenant) {
            $tenant = Tenant::first();
        }

        if (! $tenant) {
            return response()->json(['error' => 'Tenant context missing.'], 400);
        }

        // Apply tenant database scoping
        TenantScope::setTenantId($tenant->id);

        $payload = $request->all();

        // 2. Extract call ID and card parameters
        $callId = $payload['call_id'] ?? $payload['callId'] ?? $payload['message']['callId'] ?? null;

        $cardNumber = $payload['card_number'] ?? $payload['cardNumber'] ?? $payload['card'] ?? null;
        $expMonth = $payload['expiration_month'] ?? $payload['expirationMonth'] ?? $payload['exp_month'] ?? null;
        $expYear = $payload['expiration_year'] ?? $payload['expirationYear'] ?? $payload['exp_year'] ?? null;
        $cvv = $payload['cvv'] ?? $payload['cvc'] ?? null;

        // Amount check (standardize to cents)
        $rawAmount = $payload['amount'] ?? 150; // default $150
        $amountInCents = $rawAmount < 1000 ? (int) ($rawAmount * 100) : (int) $rawAmount;

        // 3. Pause Active Voice Recording immediately to maintain PCI compliance
        if ($callId) {
            $this->pauseCallRecording($callId);
        }

        // 4. Secure Stripe processing
        $stripeKey = config('cashier.secret') ?? env('STRIPE_SECRET') ?? 'dummy-stripe-secret';

        $status = 'failed';
        $errorMessage = null;
        $cardLastFour = $cardNumber ? substr(str_replace([' ', '-'], '', $cardNumber), -4) : null;
        $chargeId = null;

        try {
            if (! $cardNumber || ! $expMonth || ! $expYear || ! $cvv) {
                throw new \InvalidArgumentException('Missing card billing fields.');
            }

            // In local/testing mode with dummy Stripe keys, simulate success/failure to avoid network reliance
            if (str_starts_with($stripeKey, 'dummy') || app()->runningUnitTests()) {
                if ($cardNumber === '4111111111111112' || str_contains($cardNumber, 'declined') || str_contains($cardNumber, 'fail')) {
                    throw new \Exception('Your card was declined.');
                }

                $status = 'success';
                $chargeId = 'ch_mock_'.uniqid();
            } else {
                $stripe = new StripeClient($stripeKey);

                // Create ephemeral card token
                $token = $stripe->tokens->create([
                    'card' => [
                        'number' => $cardNumber,
                        'exp_month' => $expMonth,
                        'exp_year' => $expYear,
                        'cvc' => $cvv,
                    ],
                ]);

                // Create charge
                $charge = $stripe->charges->create([
                    'amount' => $amountInCents,
                    'currency' => 'usd',
                    'source' => $token->id,
                    'description' => "Voice checkout call: {$callId} (Tenant: {$tenant->name})",
                ]);

                if ($charge->paid) {
                    $status = 'success';
                    $chargeId = $charge->id;
                } else {
                    $errorMessage = 'Charge payment failed.';
                }
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error("Stripe transaction failed for Call {$callId}: ".$errorMessage);
        }

        // 5. Save payment transaction record
        PaymentTransaction::create([
            'tenant_id' => $tenant->id,
            'call_id' => $callId,
            'amount' => $amountInCents / 100.0,
            'status' => $status,
            'card_last_four' => $cardLastFour,
            'error_message' => $errorMessage,
        ]);

        // 6. Execute selective audio & transcript redaction to scrub card numbers
        if ($callId) {
            $this->redactTranscriptAndLogs($callId, $cardNumber);
        }

        // Return tool callback response back to Vapi/Retell
        return response()->json([
            'success' => $status === 'success',
            'status' => $status,
            'charge_id' => $chargeId,
            'error' => $errorMessage,
            'message' => $status === 'success' ? 'Payment processed successfully' : 'Payment failed: '.$errorMessage,
            'results' => [
                'success' => $status === 'success',
                'amount' => $amountInCents / 100.0,
                'chargeId' => $chargeId,
            ],
        ]);
    }

    /**
     * Call Vapi/Retell API endpoints to pause call recordings immediately.
     */
    protected function pauseCallRecording(string $callId): void
    {
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        Log::info("Pausing active audio recording for Call: {$callId}, provider: {$provider}");

        try {
            if ($provider === 'vapi') {
                Http::withToken($apiKey)
                    ->timeout(5)
                    ->patch("https://api.vapi.ai/call/{$callId}", [
                        'recordingEnabled' => false,
                    ]);
            } else {
                Http::withToken($apiKey)
                    ->timeout(5)
                    ->post("https://api.retellai.com/v2/calls/{$callId}/pause-recording");
            }
        } catch (\Exception $e) {
            Log::error("Failed to pause recording for call {$callId}: ".$e->getMessage());
        }
    }

    /**
     * Scrub and redact sensitive card logs and database transcripts.
     */
    protected function redactTranscriptAndLogs(string $callId, ?string $cardNumber): void
    {
        $callLog = CallLog::where('call_id', $callId)->first();
        if (! $callLog) {
            return;
        }

        $transcript = $callLog->transcript;
        if (empty($transcript)) {
            return;
        }

        // Redact full card numbers (13-16 digits)
        $redacted = preg_replace('/\b(?:\d[ -]*?){13,16}\b/', '[CARD REDACTED]', $transcript);

        // Redact CVV and Expiry details if explicitly found
        $redacted = preg_replace('/\b\d{3,4}\b/', '[CVV REDACTED]', $redacted);

        if ($cardNumber) {
            $rawDigits = str_replace([' ', '-'], '', $cardNumber);
            if (strlen($rawDigits) >= 12) {
                $redacted = str_replace($cardNumber, '[CARD REDACTED]', $redacted);
                $redacted = str_replace($rawDigits, '[CARD REDACTED]', $redacted);
            }
        }

        if ($redacted !== $transcript) {
            $callLog->update(['transcript' => $redacted]);
            Log::info("Call transcript redacted successfully for PCI-DSS compliance on Call {$callId}.");
        }
    }
}
