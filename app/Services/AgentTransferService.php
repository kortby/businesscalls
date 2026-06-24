<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentTransferService
{
    /**
     * Orchestrate session transfer from primary receptionist agent to specialized agent.
     */
    public function transferToAgent(CallLog $callLog, string $destinationAgentId, array $contextVars = []): array
    {
        // Enforce isolated multi-tenant database scope parameter
        TenantScope::setTenantId($callLog->tenant_id);

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        // Inherit complete parent context
        $transcriptHistory = $callLog->transcript ?? '';
        $customerPhone = $callLog->customer_phone ?? '';

        $variables = array_merge([
            'parent_call_id' => $callLog->call_id,
            'transcript_history' => $transcriptHistory,
            'customer_phone' => $customerPhone,
        ], $contextVars);

        $payload = [];

        if ($provider === 'vapi') {
            $payload = [
                'assistantId' => $destinationAgentId,
                'assistantOverrides' => [
                    'variableValues' => $variables,
                ],
            ];

            try {
                if (app()->environment('testing')) {
                    Log::info("Simulating Vapi agent transfer call: {$callLog->call_id}");
                } else {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->patch("https://api.vapi.ai/call/{$callLog->call_id}", $payload);

                    if (! $response->successful()) {
                        Log::error('Vapi agent transfer failed: '.$response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Exception during Vapi agent transfer: '.$e->getMessage());
            }
        } else {
            // Retell
            $payload = [
                'override_agent_id' => $destinationAgentId,
                'retell_llm_dynamic_variables' => $variables,
            ];

            try {
                if (app()->environment('testing')) {
                    Log::info("Simulating Retell agent transfer call: {$callLog->call_id}");
                } else {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post("https://api.retellai.com/v2/calls/{$callLog->call_id}/transfer", $payload);

                    if (! $response->successful()) {
                        Log::error('Retell agent transfer failed: '.$response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Exception during Retell agent transfer: '.$e->getMessage());
            }
        }

        return [
            'success' => true,
            'provider' => $provider,
            'payload' => $payload,
            'variables_transferred' => $variables,
        ];
    }

    /**
     * Transfer call to live human operator (via SIP URI or warm phone number) while keeping AI active.
     */
    public function transferToHuman(CallLog $callLog, string $phoneOrSip, bool $keepAiActive = true): array
    {
        TenantScope::setTenantId($callLog->tenant_id);

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        $isSip = str_starts_with($phoneOrSip, 'sip:');
        $payload = [];

        if ($provider === 'vapi') {
            $payload = [
                'destination' => [
                    'type' => $isSip ? 'sipUri' : 'number',
                    $isSip ? 'sipUri' : 'number' => $phoneOrSip,
                    'transferMode' => $keepAiActive ? 'warm' : 'blind',
                ],
            ];

            try {
                if (app()->environment('testing')) {
                    Log::info("Simulating Vapi human transfer call: {$callLog->call_id}");
                } else {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post("https://api.vapi.ai/call/{$callLog->call_id}/transfer", $payload);

                    if (! $response->successful()) {
                        Log::error('Vapi human transfer failed: '.$response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Exception during Vapi human transfer: '.$e->getMessage());
            }
        } else {
            // Retell
            $payload = [
                'destination' => $phoneOrSip,
            ];

            try {
                if (app()->environment('testing')) {
                    Log::info("Simulating Retell human transfer call: {$callLog->call_id}");
                } else {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post("https://api.retellai.com/v2/calls/{$callLog->call_id}/transfer", $payload);

                    if (! $response->successful()) {
                        Log::error('Retell human transfer failed: '.$response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Exception during Retell human transfer: '.$e->getMessage());
            }
        }

        return [
            'success' => true,
            'provider' => $provider,
            'payload' => $payload,
            'keep_ai_active' => $keepAiActive,
        ];
    }
}
