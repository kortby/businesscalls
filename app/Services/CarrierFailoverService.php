<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarrierFailoverService
{
    /**
     * Patch telephony assistant configuration to backup SIP gateway and trigger retry call.
     */
    public function orchestrateFailover(Tenant $tenant, CallLog $callLog, array $callData): array
    {
        // Enforce isolated multi-tenant parameters
        TenantScope::setTenantId($tenant->id);

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        $assistantId = $tenant->getSetting('voice_assistant_id') ?? 'default-assistant-id';

        // Retrieve backup carrier credentials from settings
        $fallbackPhoneId = $tenant->getSetting('fallback_phone_number_id') ?? 'fallback-phone-id';
        $fallbackPhoneNumber = $tenant->getSetting('fallback_phone_number') ?? '+15559998888';

        Log::warning("Multi-Carrier SIP Trunk Failover triggered for Tenant: {$tenant->id}, Call: {$callLog->call_id}");

        $patchPayload = [];
        $retryPayload = [];

        if ($provider === 'vapi') {
            // Vapi carrier failover: patch assistant with the fallback phone number ID
            $patchPayload = [
                'phoneNumberId' => $fallbackPhoneId,
            ];

            try {
                $patchResponse = Http::withToken($apiKey)
                    ->timeout(10)
                    ->patch("https://api.vapi.ai/assistant/{$assistantId}", $patchPayload);

                if (! $patchResponse->successful()) {
                    Log::error('Vapi assistant trunk patch failed: '.$patchResponse->body());
                }

                // Place the retry outbound call session
                $retryPayload = [
                    'assistantId' => $assistantId,
                    'phoneNumberId' => $fallbackPhoneId,
                    'customer' => [
                        'number' => $callLog->customer_phone,
                    ],
                ];

                $retryResponse = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.vapi.ai/call', $retryPayload);

                if (! $retryResponse->successful()) {
                    Log::error('Vapi failover retry dial failed: '.$retryResponse->body());
                }
            } catch (\Exception $e) {
                Log::error('Exception during Vapi failover execution: '.$e->getMessage());
            }
        } else {
            // Retell carrier failover: patch assistant with the fallback phone number
            $patchPayload = [
                'phone_number_id' => $fallbackPhoneId,
            ];

            try {
                $patchResponse = Http::withToken($apiKey)
                    ->timeout(10)
                    ->patch("https://api.retellai.com/v2/assistants/{$assistantId}", $patchPayload);

                if (! $patchResponse->successful()) {
                    Log::error('Retell assistant trunk patch failed: '.$patchResponse->body());
                }

                // Place the retry outbound call session
                $retryPayload = [
                    'from_number' => $fallbackPhoneNumber,
                    'to_number' => $callLog->customer_phone,
                    'override_agent_id' => $assistantId,
                ];

                $retryResponse = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.retellai.com/v2/create-phone-call', $retryPayload);

                if (! $retryResponse->successful()) {
                    Log::error('Retell failover retry dial failed: '.$retryResponse->body());
                }
            } catch (\Exception $e) {
                Log::error('Exception during Retell failover execution: '.$e->getMessage());
            }
        }

        return [
            'success' => true,
            'original_call_id' => $callLog->call_id,
            'provider' => $provider,
            'fallback_phone_id' => $fallbackPhoneId,
            'fallback_phone_number' => $fallbackPhoneNumber,
        ];
    }
}
