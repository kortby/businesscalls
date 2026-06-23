<?php

namespace App\Http\Controllers\Api;

use App\Events\CallAnalyzed;
use App\Events\CallEnded;
use App\Events\CallStarted;
use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallWebhookController extends Controller
{
    /**
     * Handle incoming telephony events from Retell/Vapi.
     */
    public function handle(Request $request, ?string $tenant_id = null): JsonResponse
    {
        // 1. Resolve tenant ID from route parameter, query parameter or body
        $resolvedTenantId = $tenant_id
            ?? $request->query('tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->input('message.tenantId') // optional vapi nesting
            ?? $request->input('tenant_slug');

        if (! $resolvedTenantId) {
            return response()->json(['error' => 'Tenant ID is required.'], 400);
        }

        // Find tenant by ID or slug
        $tenant = Tenant::where('id', $resolvedTenantId)
            ->orWhere('slug', $resolvedTenantId)
            ->first();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        // 2. Validate HMAC Signature using active tenant's secret key
        $signature = $request->header('X-Retell-Signature')
            ?? $request->header('X-Vapi-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('x-vapi-signature')
            ?? $request->header('x-signature');

        if ($tenant->secret_key) {
            if (! $signature) {
                return response()->json(['error' => 'Signature header missing.'], 401);
            }

            $computedSignature = hash_hmac('sha256', $request->getContent(), $tenant->secret_key);
            if (! hash_equals($computedSignature, $signature)) {
                return response()->json(['error' => 'Invalid webhook signature.'], 401);
            }
        }

        // 3. Parse call payload (Retell/Vapi payloads)
        $event = $request->input('event') ?? $request->input('type');
        $callData = $request->input('call') ?? $request->input('message.call') ?? $request->all();

        // Standardize event names if necessary (e.g. vapi uses call.started)
        if ($event === 'call.started') {
            $event = 'call_started';
        } elseif ($event === 'call.ended') {
            $event = 'call_ended';
        } elseif ($event === 'call.analyzed') {
            $event = 'call_analyzed';
        }

        $callId = $callData['call_id'] ?? $callData['id'] ?? $request->input('message.callId') ?? null;
        if (! $callId) {
            return response()->json(['error' => 'Call ID missing in payload.'], 400);
        }

        $customerPhone = $callData['customer_phone_number']
            ?? $callData['customer_phone']
            ?? $callData['phone_number']
            ?? $request->input('message.customer.number')
            ?? 'Unknown';

        // Bypass TenantScope to execute multi-tenant DB updates in system mode
        TenantScope::setTenantId($tenant->id);

        switch ($event) {
            case 'call_started':
                $callLog = CallLog::updateOrCreate(
                    ['call_id' => $callId],
                    [
                        'tenant_id' => $tenant->id,
                        'status' => 'ongoing',
                        'customer_phone' => $customerPhone,
                    ]
                );

                event(new CallStarted($tenant->id, $callLog));
                break;

            case 'call_ended':
                $duration = $callData['duration_seconds'] ?? $callData['duration'] ?? null;
                $recordingUrl = $callData['recording_url'] ?? null;

                $callLog = CallLog::updateOrCreate(
                    ['call_id' => $callId],
                    [
                        'tenant_id' => $tenant->id,
                        'status' => 'ended',
                        'customer_phone' => $customerPhone,
                        'duration' => $duration,
                        'recording_url' => $recordingUrl,
                    ]
                );

                event(new CallEnded($tenant->id, $callLog));
                break;

            case 'call_analyzed':
                $transcript = $callData['transcript'] ?? null;
                $summary = $callData['summary'] ?? null;

                if (is_array($summary)) {
                    $summary = json_encode($summary);
                }

                $callLog = CallLog::updateOrCreate(
                    ['call_id' => $callId],
                    [
                        'tenant_id' => $tenant->id,
                        'status' => 'ended',
                        'customer_phone' => $customerPhone,
                        'transcript' => $transcript,
                        'summary' => $summary,
                    ]
                );

                event(new CallAnalyzed($tenant->id, $callLog));
                break;

            default:
                Log::warning("Unhandled call event: {$event}");
                break;
        }

        return response()->json(['success' => true]);
    }
}
