<?php

namespace App\Http\Controllers\Api;

use App\Events\CallAnalyzed;
use App\Events\CallEnded;
use App\Events\CallStarted;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessLatencyDriftJob;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\CarrierFailoverService;
use App\Services\ComplianceSanitizerService;
use App\Services\SentimentEvaluationService;
use App\Services\VoicemailParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        $vapiSecret = $request->header('X-Vapi-Secret') ?? $request->header('x-vapi-secret');
        $retellSecret = $request->header('X-Retell-Secret') ?? $request->header('x-retell-secret');
        $authToken = $request->bearerToken();

        $hasCustomCredentials = false;
        if ($tenant->secret_key) {
            $hasCustomCredentials = ($authToken && hash_equals($tenant->secret_key, $authToken))
                || ($vapiSecret && hash_equals($tenant->secret_key, $vapiSecret))
                || ($retellSecret && hash_equals($tenant->secret_key, $retellSecret));
        }

        if ($tenant->secret_key && ! $hasCustomCredentials) {
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

        // 4. Answering Machine Detection (AMD) handling
        $amdStatus = null;
        $vapiAmd = $callData['answeringMachineDetectionResult']
            ?? $request->input('message.call.answeringMachineDetectionResult')
            ?? $request->input('message.answeringMachineDetectionResult')
            ?? $request->input('answeringMachineDetectionResult');

        if ($vapiAmd) {
            if (in_array($vapiAmd, ['machine', 'answering_machine', 'voicemail'])) {
                $amdStatus = 'machine';
            } elseif ($vapiAmd === 'human') {
                $amdStatus = 'human';
            }
        }

        $retellAmd = $callData['machine_detection_result']
            ?? $request->input('machine_detection_result');

        if ($retellAmd) {
            if (in_array($retellAmd, ['machine_answered', 'machine', 'voicemail'])) {
                $amdStatus = 'machine';
            } elseif (in_array($retellAmd, ['human_answered', 'human'])) {
                $amdStatus = 'human';
            }
        }

        if (! $amdStatus) {
            $genericAmd = $request->input('amd_status') ?? $request->input('machine');
            if ($genericAmd !== null) {
                if ($genericAmd === 'machine' || $genericAmd === true || $genericAmd === 'true') {
                    $amdStatus = 'machine';
                } elseif ($genericAmd === 'human' || $genericAmd === false || $genericAmd === 'false') {
                    $amdStatus = 'human';
                }
            }
        }

        if ($amdStatus && $callId) {
            $bookingId = Cache::get("call_booking_map:{$callId}");
            if ($bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking) {
                    if ($amdStatus === 'machine') {
                        // Outbound Answering Machine voicemail drop API call
                        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
                        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));

                        $scheduledStart = $booking->scheduled_start ? $booking->scheduled_start->format('Y-m-d H:i') : now()->format('Y-m-d H:i');
                        $employee = $booking->employee;
                        $alertText = 'Hi '.($employee ? $employee->first_name : 'technician').", you have been assigned a new HVAC dispatch at {$scheduledStart}. Please check your portal.";

                        try {
                            if ($provider === 'vapi') {
                                Http::withToken($apiKey)->post("https://api.vapi.ai/call/{$callId}/voicemail-drop", [
                                    'message' => $alertText,
                                ]);
                            } else {
                                Http::withToken($apiKey)->post("https://api.retellai.com/v2/calls/{$callId}/voicemail-drop", [
                                    'text' => $alertText,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Voicemail drop API call failed for Call: {$callId}: ".$e->getMessage());
                        }

                        $booking->status = 'voicemail_alerted';
                        $booking->save();
                        Log::info("Booking ID {$booking->id} transitioned to voicemail_alerted via AMD webhook.");
                    } elseif ($amdStatus === 'human') {
                        $booking->status = 'booked';
                        $booking->save();
                        Log::info("Booking ID {$booking->id} transitioned to booked via AMD webhook.");
                    }
                }
            }
        }

        // VoIP Spam call filter (reject if >= 3 failed/short calls in last 10 mins)
        if ($customerPhone !== 'Unknown') {
            $failedCallsCount = CallLog::where('customer_phone', $customerPhone)
                ->where(function ($query) {
                    $query->where('status', 'error')
                        ->orWhere(function ($q) {
                            $q->where('status', 'ended')->where('duration', '<', 2);
                        });
                })
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($failedCallsCount >= 3) {
                Log::warning("VoIP Spam detected: rejecting webhook call for number {$customerPhone} on tenant {$tenant->id}");

                return response()->json(['error' => 'Call rejected due to spam activity.'], 429);
            }
        }

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

                $rawReason = $callData['disconnection_reason'] ?? $callData['endReason'] ?? $callData['end_reason'] ?? $request->input('message.call.endReason') ?? $request->input('endReason') ?? null;
                $callEndReason = null;
                if ($rawReason) {
                    $normalized = str_replace('-', '_', strtolower($rawReason));
                    if (str_contains($normalized, 'user') || str_contains($normalized, 'customer') || $normalized === 'user_hung_up') {
                        $callEndReason = 'user_hung_up';
                    } elseif (str_contains($normalized, 'agent') || str_contains($normalized, 'assistant') || $normalized === 'agent_hung_up') {
                        $callEndReason = 'agent_hung_up';
                    } elseif (str_contains($normalized, 'dial_failed') || str_contains($normalized, 'dial') || $normalized === 'dial_failed') {
                        $callEndReason = 'dial_failed';
                    } elseif (str_contains($normalized, 'error') || $normalized === 'error') {
                        $callEndReason = 'error';
                    } else {
                        $callEndReason = $normalized;
                    }
                }

                $disconnectionSource = $callData['disconnection_source'] ?? $callData['disconnectionSource'] ?? $request->input('message.call.disconnectionSource') ?? $request->input('disconnectionSource') ?? null;

                $latency = $callData['monitor']['latency']['average'] ?? $callData['average_latency'] ?? $callData['latency'] ?? $request->input('message.call.monitor.latency.average') ?? null;

                $transcriptionConfidence = $callData['analysis']['transcriptionConfidence'] ?? $callData['transcription_confidence'] ?? $request->input('message.call.analysis.transcriptionConfidence') ?? null;

                $toolCalls = $callData['tool_calls'] ?? $callData['toolCalls'] ?? $request->input('message.call.toolCalls') ?? [];
                $toolCallsCount = count($toolCalls);
                $successfulToolCalls = 0;
                if ($toolCallsCount > 0) {
                    foreach ($toolCalls as $tc) {
                        $isError = $tc['isError'] ?? $tc['error'] ?? (($tc['result']['status'] ?? '') === 'error') ?? false;
                        if (! $isError) {
                            $successfulToolCalls++;
                        }
                    }
                    $toolSuccessRate = $successfulToolCalls / $toolCallsCount;
                } else {
                    $toolSuccessRate = 1.0;
                }

                $existingCallLog = CallLog::where('call_id', $callId)->first();
                $finalEndReason = ($existingCallLog?->call_end_reason === 'forwarded_to_voicemail' && $callEndReason !== 'error')
                    ? 'forwarded_to_voicemail'
                    : ($callEndReason ?? $existingCallLog?->call_end_reason);

                $cost = $callData['combined_cost'] ?? $callData['combinedCost'] ?? $callData['cost'] ?? $callData['call_cost'] ?? null;

                $callLog = CallLog::updateOrCreate(
                    ['call_id' => $callId],
                    [
                        'tenant_id' => $tenant->id,
                        'status' => 'ended',
                        'customer_phone' => $customerPhone,
                        'duration' => $duration,
                        'recording_url' => $recordingUrl,
                        'call_end_reason' => $finalEndReason,
                        'disconnection_source' => $disconnectionSource,
                        'cost' => $cost !== null ? (float) $cost : null,
                    ]
                );

                if ($finalEndReason === 'dial_failed' || $finalEndReason === 'dial_busy') {
                    app(CarrierFailoverService::class)->orchestrateFailover($tenant, $callLog, $callData);
                }

                $callLog->calculateCqsScore(
                    $latency !== null ? (int) $latency : null,
                    $transcriptionConfidence !== null ? (float) $transcriptionConfidence : null,
                    (float) $toolSuccessRate
                );

                $ratings = $request->input('survey_scores')
                    ?? $request->input('call.survey_scores')
                    ?? $request->input('csat_ratings')
                    ?? $request->input('call.csat_ratings')
                    ?? $callData['survey_scores']
                    ?? $callData['csat_ratings']
                    ?? [];

                if (is_string($ratings)) {
                    $ratings = json_decode($ratings, true) ?? [];
                }

                if (is_array($ratings) && ! empty($ratings)) {
                    $callLog->calculateCsatScore($ratings);
                }

                event(new CallEnded($tenant->id, $callLog));
                break;

            case 'call_analyzed':
                $transcript = $callData['transcript'] ?? null;
                $summary = $callData['summary'] ?? null;

                if (is_array($summary)) {
                    $summary = json_encode($summary);
                }

                $sanitizer = app(ComplianceSanitizerService::class);
                $transcript = $sanitizer->sanitize($transcript);
                $summary = $sanitizer->sanitize($summary);

                $rawReason = $callData['disconnection_reason'] ?? $callData['endReason'] ?? $callData['end_reason'] ?? $request->input('message.call.endReason') ?? $request->input('endReason') ?? null;
                $callEndReason = null;
                if ($rawReason) {
                    $normalized = str_replace('-', '_', strtolower($rawReason));
                    if (str_contains($normalized, 'user') || str_contains($normalized, 'customer') || $normalized === 'user_hung_up') {
                        $callEndReason = 'user_hung_up';
                    } elseif (str_contains($normalized, 'agent') || str_contains($normalized, 'assistant') || $normalized === 'agent_hung_up') {
                        $callEndReason = 'agent_hung_up';
                    } elseif (str_contains($normalized, 'dial_failed') || str_contains($normalized, 'dial') || $normalized === 'dial_failed') {
                        $callEndReason = 'dial_failed';
                    } elseif (str_contains($normalized, 'error') || $normalized === 'error') {
                        $callEndReason = 'error';
                    } else {
                        $callEndReason = $normalized;
                    }
                }

                $disconnectionSource = $callData['disconnection_source'] ?? $callData['disconnectionSource'] ?? $request->input('message.call.disconnectionSource') ?? $request->input('disconnectionSource') ?? null;

                $latency = $callData['monitor']['latency']['average'] ?? $callData['average_latency'] ?? $callData['latency'] ?? $request->input('message.call.monitor.latency.average') ?? null;

                $transcriptionConfidence = $callData['analysis']['transcriptionConfidence'] ?? $callData['transcription_confidence'] ?? $request->input('message.call.analysis.transcriptionConfidence') ?? null;

                $toolCalls = $callData['tool_calls'] ?? $callData['toolCalls'] ?? $request->input('message.call.toolCalls') ?? [];
                $toolCallsCount = count($toolCalls);
                $successfulToolCalls = 0;
                if ($toolCallsCount > 0) {
                    foreach ($toolCalls as $tc) {
                        $isError = $tc['isError'] ?? $tc['error'] ?? (($tc['result']['status'] ?? '') === 'error') ?? false;
                        if (! $isError) {
                            $successfulToolCalls++;
                        }
                    }
                    $toolSuccessRate = $successfulToolCalls / $toolCallsCount;
                } else {
                    $toolSuccessRate = 1.0;
                }

                $existingCallLog = CallLog::where('call_id', $callId)->first();
                $finalEndReason = ($existingCallLog?->call_end_reason === 'forwarded_to_voicemail' && $callEndReason !== 'error')
                    ? 'forwarded_to_voicemail'
                    : ($callEndReason ?? $existingCallLog?->call_end_reason);

                $callLog = CallLog::updateOrCreate(
                    ['call_id' => $callId],
                    [
                        'tenant_id' => $tenant->id,
                        'status' => 'ended',
                        'customer_phone' => $customerPhone,
                        'transcript' => $transcript,
                        'summary' => $summary,
                        'call_end_reason' => $finalEndReason,
                        'disconnection_source' => $disconnectionSource,
                    ]
                );

                $callLog->calculateCqsScore(
                    $latency !== null ? (int) $latency : null,
                    $transcriptionConfidence !== null ? (float) $transcriptionConfidence : null,
                    (float) $toolSuccessRate
                );

                $ratings = $request->input('survey_scores')
                    ?? $request->input('call.survey_scores')
                    ?? $request->input('csat_ratings')
                    ?? $request->input('call.csat_ratings')
                    ?? $callData['survey_scores']
                    ?? $callData['csat_ratings']
                    ?? [];

                if (is_string($ratings)) {
                    $ratings = json_decode($ratings, true) ?? [];
                }

                if (is_array($ratings) && ! empty($ratings)) {
                    $callLog->calculateCsatScore($ratings);
                }

                // If the call was routed to voicemail, trigger parser
                if ($finalEndReason === 'forwarded_to_voicemail') {
                    app(VoicemailParserService::class)->parseVoicemail($callLog);
                }

                event(new CallAnalyzed($tenant->id, $callLog));
                break;

            case 'transcript':
                // Live transcription chunk webhook
                $transcriptText = $request->input('transcript')
                    ?? $request->input('message.transcript.text')
                    ?? $request->input('message.transcript.message')
                    ?? $request->input('text')
                    ?? '';

                if (! empty($transcriptText)) {
                    // 1. Evaluate call sentiment & supervisor auto-escalation
                    $sentimentService = app(SentimentEvaluationService::class);
                    $sentimentService->evaluateTurn($callId, $transcriptText, $tenant->id);

                    // 2. Multilingual Voice Hot-Swapping
                    $this->handleLanguageHotSwap($callId, $transcriptText, $tenant);

                    // 3. Real-Time Speech Pace & Rate Congruence Engine
                    $this->handleSpeechPaceAlignment($callId, $transcriptText, $tenant, $request);
                }
                break;

            default:
                Log::warning("Unhandled call event: {$event}");
                break;
        }

        // Parse and process latency telemetry if present in payload
        $telemetry = $request->input('performance_metrics')
            ?? $request->input('latency_telemetry')
            ?? ($callData['performance_metrics'] ?? null)
            ?? ($callData['latency_telemetry'] ?? null)
            ?? $request->input('message.call.performance_metrics')
            ?? $request->input('message.call.latency_telemetry')
            ?? null;

        if ($telemetry) {
            ProcessLatencyDriftJob::dispatch($tenant->id, $callId, $telemetry);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Detect Spanish or French phrases and hot-swap active call language synthesis voice.
     */
    protected function handleLanguageHotSwap(string $callId, string $text, Tenant $tenant): void
    {
        $text = strtolower($text);

        $detectedLang = 'en';
        if (preg_match('/\b(hola|como|gracias|buenos|dias|tengo|problema|calefaccion|aire)\b/', $text)) {
            $detectedLang = 'es';
        } elseif (preg_match('/\b(bonjour|merci|oui|probleme|chauffage|climatisation|panne|aide)\b/', $text)) {
            $detectedLang = 'fr';
        } else {
            return;
        }

        // Prevent duplicate updates if already swapped
        $cacheKey = "call-language-swapped:{$callId}";
        if (Cache::get($cacheKey) === $detectedLang) {
            return;
        }

        Cache::put($cacheKey, $detectedLang, 600);

        Log::info("Language switch detected for Call: {$callId}. Hot-swapping voice system to language: {$detectedLang}");

        $voiceId = 'Rachel'; // ElevenLabs multilingual voice identifier
        $aiPrompt = '';

        if ($detectedLang === 'es') {
            $aiPrompt = 'Usted es el despachador de voz de IA de la empresa. Por favor, actúe de manera profesional, amable y eficiente. Use terminología HVAC correcta en español.';
        } elseif ($detectedLang === 'fr') {
            $aiPrompt = 'Vous êtes le répartiteur vocal IA de l\'entreprise. Veuillez agir de manière professionnelle, amicale et efficace. Utilisez la terminologie CVC correcte en français.';
        }

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        try {
            if ($provider === 'vapi') {
                Http::withToken($apiKey)->patch("https://api.vapi.ai/call/{$callId}", [
                    'assistantOverrides' => [
                        'transcriber' => [
                            'provider' => 'deepgram',
                            'language' => $detectedLang,
                        ],
                        'voice' => [
                            'provider' => 'elevenlabs',
                            'voiceId' => $voiceId,
                            'model' => 'eleven_multilingual_v2',
                        ],
                        'model' => [
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => $aiPrompt,
                                ],
                            ],
                        ],
                    ],
                ]);
            } else {
                Http::withToken($apiKey)->patch("https://api.retellai.com/v2/calls/{$callId}", [
                    'assistant_overrides' => [
                        'voice_id' => $voiceId,
                        'language' => $detectedLang,
                        'prompt' => $aiPrompt,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to hot-swap telephony language for Call ID: {$callId}: ".$e->getMessage());
        }
    }

    /**
     * Monitor live transcription turn timestamps, calculate speech rate congruence,
     * and dynamically patch speaking speed mid-call if pace mismatch occurs.
     */
    protected function handleSpeechPaceAlignment(string $callId, string $text, Tenant $tenant, Request $request): void
    {
        $words = explode(' ', trim($text));
        $wordCount = count(array_filter($words));

        if ($wordCount === 0) {
            return;
        }

        // Get speaker role or segment type to ensure we only analyze the user/customer, not the agent
        $role = $request->input('message.transcript.role')
            ?? $request->input('role')
            ?? $request->input('speaker')
            ?? 'user';

        if ($role !== 'user' && $role !== 'customer') {
            return;
        }

        $startTime = (float) ($request->input('start_time')
            ?? $request->input('message.transcript.startTime')
            ?? $request->input('message.transcript.start')
            ?? 0.0);

        $endTime = (float) ($request->input('end_time')
            ?? $request->input('message.transcript.endTime')
            ?? $request->input('message.transcript.end')
            ?? 0.0);

        $duration = $endTime - $startTime;
        if ($duration <= 0.0) {
            $duration = (float) ($request->input('duration')
                ?? $request->input('message.transcript.duration')
                ?? 0.0);
        }

        $targetPace = 2.0; // Default active assistant speaking pace (words per second)

        // Handle division-by-zero boundary values elegantly under erratic caller pause cycles
        if ($duration <= 0.0) {
            $userPace = $targetPace;
        } else {
            $userPace = $wordCount / $duration;
        }

        $maxPace = max($userPace, $targetPace);
        if ($maxPace <= 0.0) {
            $congruence = 1.0;
        } else {
            $congruence = 1.0 - (abs($userPace - $targetPace) / $maxPace);
        }

        Log::info("Speech Pace Alignment check: User Pace = {$userPace} W/S, Assistant Pace = {$targetPace} W/S, Congruence = {$congruence}");

        // If mismatch detected (congruence < 0.70), update assistant pace by +/- 15%
        if ($congruence < 0.70) {
            // If already adjusted in last 60 seconds, skip to avoid rapid pacing jitter
            $throttleKey = "speech-pace-adjusted:{$callId}";
            if (Cache::has($throttleKey)) {
                return;
            }
            Cache::put($throttleKey, true, 60);

            // Speed multiplier: fast speaker -> speed up by +15% (1.15), slow speaker -> slow down by -15% (0.85)
            $newSpeed = ($userPace > $targetPace) ? 1.15 : 0.85;

            Log::info("Speech pace mismatch detected (Congruence = {$congruence}). Patching assistant speed to {$newSpeed} for Call: {$callId}");

            $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
            $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

            try {
                if ($provider === 'vapi') {
                    Http::withToken($apiKey)->patch("https://api.vapi.ai/call/{$callId}", [
                        'assistantOverrides' => [
                            'voice' => [
                                'speed' => $newSpeed,
                            ],
                        ],
                    ]);
                } else {
                    Http::withToken($apiKey)->patch("https://api.retellai.com/v2/calls/{$callId}", [
                        'assistant_overrides' => [
                            'speed' => $newSpeed,
                        ],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to patch speech pace for Call: {$callId}: ".$e->getMessage());
            }
        }
    }
}
