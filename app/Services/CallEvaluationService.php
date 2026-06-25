<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallEvaluationService
{
    /**
     * Run automated semantic checks on call transcript / metadata,
     * calculate Theta_eval score, and save it on the CallLog.
     */
    public function evaluateCall(CallLog $callLog): float
    {
        $tenant = $callLog->tenant;
        if (! $tenant) {
            Log::error("Call log {$callLog->id} has no associated tenant.");

            return 0.0;
        }

        // Maintain strict isolation context
        $originalTenantId = TenantScope::getTenantId();
        TenantScope::setTenantId($tenant->id);

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
        $callId = $callLog->call_id;

        Log::info("Running CallEvaluationService for call: {$callId}, provider: {$provider}");

        $checks = 0;
        $violations = 0;
        $intent = 1; // Default to successful dispatch unless failure detected

        try {
            if ($provider === 'vapi') {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->get("https://api.vapi.ai/call/{$callId}");
            } else {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->get("https://api.retellai.com/get-call/{$callId}");
            }

            if ($response->successful()) {
                $data = $response->json();

                // Extract intent accomplished
                $intentVal = $data['analysis']['intentAccomplished']
                    ?? $data['analysis']['intent_accomplished']
                    ?? $data['analysis']['success']
                    ?? $data['intent_accomplished']
                    ?? $data['success']
                    ?? null;

                if ($intentVal !== null) {
                    $intent = $intentVal ? 1 : 0;
                } else {
                    // Fallback to checking the summary or transcript or call_end_reason
                    $textToSearch = strtolower(($callLog->summary ?? '').' '.($callLog->transcript ?? ''));
                    if (str_contains($textToSearch, 'fail') || str_contains($textToSearch, 'could not') || str_contains($textToSearch, 'error')) {
                        $intent = 0;
                    }
                }

                // Extract scorecard checks
                $evalList = $data['analysis']['evaluations']
                    ?? $data['analysis']['scorecard']
                    ?? $data['analysis']['structuredData']
                    ?? $data['evaluations']
                    ?? $data['scorecard']
                    ?? null;

                if (is_array($evalList)) {
                    foreach ($evalList as $key => $val) {
                        $checks++;
                        // If it's a list of objects, e.g., [{"name": "...", "passed": true}]
                        if (is_array($val)) {
                            $passed = $val['passed'] ?? $val['success'] ?? $val['result'] ?? true;
                            if (is_string($passed)) {
                                $passed = ! in_array(strtolower($passed), ['fail', 'false', 'failed', 'violation']);
                            }
                            if (! $passed) {
                                $violations++;
                            }
                        } else {
                            // If it's a simple key-value pair, e.g., "collected_details" => false
                            $passed = (bool) $val;
                            if (! $passed) {
                                $violations++;
                            }
                        }
                    }
                } else {
                    // If no explicit checklist array/object returned, parse custom check definitions.
                    // For example, mock checks can be defined as default.
                    $checks = 3;
                    $transcript = strtolower($callLog->transcript ?? '');

                    // Violation: did agent hallucinate?
                    if (str_contains($transcript, 'hallucinate') || str_contains($transcript, 'incorrect information')) {
                        $violations++;
                    }
                    // Violation: was agent impolite?
                    if (str_contains($transcript, 'rude') || str_contains($transcript, 'impolite') || str_contains($transcript, 'angry')) {
                        $violations++;
                    }
                    // Violation: failed to collect details?
                    if (str_contains($transcript, 'did not collect') || str_contains($transcript, 'missing name')) {
                        $violations++;
                    }
                }
            } else {
                Log::warning('Telephony API eval fetch failed: '.$response->body().'. Using fallback checks.');
                // Fallback checks
                $checks = 3;
                $transcript = strtolower($callLog->transcript ?? '');
                if (str_contains($transcript, 'hallucinate') || str_contains($transcript, 'incorrect information')) {
                    $violations++;
                }
                if (str_contains($transcript, 'rude') || str_contains($transcript, 'impolite') || str_contains($transcript, 'angry')) {
                    $violations++;
                }
                if (str_contains($transcript, 'did not collect') || str_contains($transcript, 'missing name')) {
                    $violations++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Exception in CallEvaluationService: '.$e->getMessage());
            // Fallback checks
            $checks = 3;
            $transcript = strtolower($callLog->transcript ?? '');
            if (str_contains($transcript, 'hallucinate')) {
                $violations++;
            }
        }

        // Calculate Theta_eval
        $thetaEval = ($checks > 0 ? (1.0 - ($violations / $checks)) : 1.0) * $intent;
        $thetaEval = max(0.0, min(1.0, (float) $thetaEval));

        $callLog->conversational_eval_score = $thetaEval;
        $callLog->save();

        Log::info("Call {$callId} Theta_eval computed: {$thetaEval} (Checks: {$checks}, Violations: {$violations}, Intent: {$intent})");

        // Restore original tenant context
        TenantScope::setTenantId($originalTenantId);

        return $thetaEval;
    }
}
