<?php

namespace App\Http\Controllers\Api;

use App\Events\DispatchUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\SendTechnicianAlertJob;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantOAuthToken;
use App\Services\DispatchRebalancerService;
use App\Services\WorkflowIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookController extends Controller
{
    /**
     * Handle the voice AI dispatch event.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Parse incoming parameters (supporting flat and nested Vapi payload structures)
        $toolCallId = $request->input('message.toolCalls.0.id');
        $arguments = $request->input('message.toolCalls.0.function.arguments', []);
        $functionName = $request->input('message.toolCalls.0.function.name') ?? $request->input('function_name');

        $tenantIdOrSlug = $arguments['tenant_id'] ?? $request->input('tenant_id') ?? $arguments['tenant_slug'] ?? $request->input('tenant_slug') ?? $request->input('tenant_id');

        if (! $tenantIdOrSlug) {
            return response()->json([
                'error' => 'Missing required field: tenant_id or tenant_slug must be provided.',
            ], 400);
        }

        // 2. Resolve Tenant
        $tenant = Tenant::where('id', $tenantIdOrSlug)
            ->orWhere('slug', $tenantIdOrSlug)
            ->first();

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found.',
            ], 404);
        }

        // 3. Security Check: Validate Custom Credentials or HMAC SHA256 Signature
        $signature = $request->header('x-vapi-signature') ?? $request->header('x-signature');
        $vapiSecret = $request->header('X-Vapi-Secret') ?? $request->header('x-vapi-secret');
        $retellSecret = $request->header('X-Retell-Secret') ?? $request->header('x-retell-secret');
        $authToken = $request->bearerToken();

        $hasCustomCredentials = false;
        if ($tenant->secret_key) {
            $hasCustomCredentials = ($authToken && hash_equals($tenant->secret_key, $authToken))
                || ($vapiSecret && hash_equals($tenant->secret_key, $vapiSecret))
                || ($retellSecret && hash_equals($tenant->secret_key, $retellSecret));
        }

        if (! $hasCustomCredentials && $authToken) {
            $tokenRecord = TenantOAuthToken::where('access_token', $authToken)->first();
            if ($tokenRecord && $tokenRecord->tenant_id === $tenant->id && ! $tokenRecord->expires_at->isPast()) {
                $hasCustomCredentials = true;
            }
        }

        if ($tenant->secret_key && ! $hasCustomCredentials) {
            $computed = hash_hmac('sha256', $request->getContent(), $tenant->secret_key);
            if (! $signature || ! hash_equals($computed, $signature)) {
                // Broadcast authorization error to dashboard
                event(new DispatchUpdated($tenant->id, [
                    'type' => 'error',
                    'message' => 'HMAC validation failed: unauthorized webhook request.',
                ]));

                $unauthorizedResult = [
                    'status' => 'error',
                    'message' => 'HMAC verification failed.',
                ];

                if ($toolCallId) {
                    return response()->json([
                        'results' => [
                            [
                                'toolCallId' => $toolCallId,
                                'result' => $unauthorizedResult,
                            ],
                        ],
                    ], 401);
                }

                return response()->json($unauthorizedResult, 401);
            }
        }

        // Apply global tenancy context for this request thread
        TenantScope::setTenantId($tenant->id);

        // Call-Steering Matrix: Emergency & High-Severity Outage check
        $isEmergency = false;
        $serviceType = $arguments['service_type'] ?? $request->input('service_type') ?? '';
        $jobDetails = $arguments['job_details'] ?? $request->input('job_details') ?? '';
        $transcript = $request->input('message.transcript') ?? $request->input('transcript') ?? '';

        $emergencyKeywords = ['emergency', 'gas leak', 'electrical short circuit', 'outage', 'short circuit', 'leak', 'short-circuit'];
        foreach ($emergencyKeywords as $kw) {
            if (str_contains(strtolower($serviceType), $kw) || str_contains(strtolower($jobDetails), $kw) || str_contains(strtolower($transcript), $kw)) {
                $isEmergency = true;
                break;
            }
        }

        if ($isEmergency) {
            $callId = $request->input('call_id')
                ?? $request->input('call.id')
                ?? $request->input('message.call.id')
                ?? $request->input('message.callId');

            if ($callId) {
                $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
                $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

                $emergencyScript = 'EMERGENCY SCRIPT: Act with absolute urgency. A high-priority emergency or outage has been reported. Prioritize safety instructions immediately and secure the location.';

                try {
                    if ($provider === 'vapi') {
                        Http::withToken($apiKey)->timeout(5)->patch("https://api.vapi.ai/call/{$callId}", [
                            'assistantOverrides' => [
                                'voice' => [
                                    'provider' => 'elevenlabs',
                                    'voiceId' => 'pNInz6obpgfrDuZJe63m', // Authoritative Voice ID
                                ],
                                'model' => [
                                    'messages' => [
                                        [
                                            'role' => 'system',
                                            'content' => $emergencyScript,
                                        ],
                                    ],
                                ],
                                'stopSpeakingThreshold' => 2.0,
                                'silenceTimeoutSeconds' => 5.0,
                            ],
                        ]);
                    } else {
                        Http::withToken($apiKey)->timeout(5)->patch("https://api.retellai.com/v2/calls/{$callId}", [
                            'assistant_overrides' => [
                                'voice_id' => '11labs-authoritative',
                                'prompt' => $emergencyScript,
                                'stop_speaking_threshold' => 2.0,
                            ],
                        ]);
                    }
                    Log::info("Call-Steering Matrix: Emergency safety overrides patched successfully for Call {$callId}.");
                } catch (\Exception $e) {
                    Log::error('Failed to patch emergency safety overrides: '.$e->getMessage());
                }
            }
        }

        // Check if this is an integration tool call trigger
        if ($functionName && in_array($functionName, ['trigger_workflow', 'triggerExternalWorkflow'])) {
            $eventName = $arguments['event_name'] ?? $request->input('event_name') ?? 'default_event';

            $workflowService = app(WorkflowIntegrationService::class);
            $workflowService->triggerExternalWorkflow($tenant, $eventName, $arguments);

            $result = [
                'status' => 'success',
                'message' => "Workflow '{$eventName}' triggered successfully.",
            ];

            if ($toolCallId) {
                return response()->json([
                    'results' => [
                        [
                            'toolCallId' => $toolCallId,
                            'result' => $result,
                        ],
                    ],
                ]);
            }

            return response()->json($result);
        }

        $customerPhone = $arguments['customer_phone'] ?? $request->input('customer_phone');
        $serviceType = $arguments['service_type'] ?? $request->input('service_type');
        $requestedTime = $arguments['requested_time'] ?? $request->input('requested_time');

        if (! $customerPhone || ! $serviceType || ! $requestedTime) {
            return response()->json([
                'error' => 'Missing required fields: customer_phone, service_type, and requested_time must be provided.',
            ], 400);
        }

        // Triage priority classification
        $waterLeak = $arguments['water_leak'] ?? $request->input('water_leak') ?? $arguments['waterActiveLeak'] ?? $request->input('waterActiveLeak') ?? null;
        $outdoorTemp = $arguments['outdoor_temp'] ?? $request->input('outdoor_temp') ?? $arguments['outdoorTemp'] ?? $request->input('outdoorTemp') ?? null;
        $sparkingOutlets = $arguments['sparking_outlets'] ?? $request->input('sparking_outlets') ?? $arguments['sparkingOutlets'] ?? $request->input('sparkingOutlets') ?? null;
        $partialOutage = $arguments['partial_outage'] ?? $request->input('partial_outage') ?? $arguments['partialOutage'] ?? $request->input('partialOutage') ?? null;
        $burningSmell = $arguments['burning_smell'] ?? $request->input('burning_smell') ?? $arguments['burningSmell'] ?? $request->input('burningSmell') ?? null;
        $emergencyTriage = $arguments['emergency_triage'] ?? $request->input('emergency_triage') ?? $arguments['emergencyTriage'] ?? $request->input('emergencyTriage') ?? null;

        $priorityState = 'routine_maintenance';
        if (strtolower($serviceType) === 'plumbing' && ($waterLeak === true || $waterLeak === 'yes' || $waterLeak === 1 || $waterLeak === '1')) {
            $priorityState = 'emergency';
        } elseif (strtolower($serviceType) === 'hvac' && $outdoorTemp !== null && ((float) $outdoorTemp < 32 || (float) $outdoorTemp > 95)) {
            $priorityState = 'emergency';
        } elseif (strtolower($serviceType) === 'electrical' && ($sparkingOutlets || $partialOutage || $burningSmell)) {
            $priorityState = 'emergency';
        } elseif ($emergencyTriage === true || $emergencyTriage === 'yes' || $emergencyTriage === 1 || $emergencyTriage === '1') {
            $priorityState = 'emergency';
        }

        // Certification Filter
        $requiredCert = $arguments['required_certification'] ?? $request->input('required_certification') ?? null;
        if (! $requiredCert) {
            $desc = strtolower($jobDetails.' '.$transcript);
            if (strtolower($serviceType) === 'hvac' && (str_contains($desc, 'refrigerant') || str_contains($desc, 'freon') || str_contains($desc, 'coolant') || str_contains($desc, 'ac repair'))) {
                $requiredCert = 'EPA_608';
            } elseif (strtolower($serviceType) === 'plumbing' && (str_contains($desc, 'main line') || str_contains($desc, 'master'))) {
                $requiredCert = 'Master_Plumber';
            }
        }

        // Parse coordinates
        $jobLat = (float) ($arguments['latitude'] ?? $request->input('latitude') ?? 37.7749);
        $jobLng = (float) ($arguments['longitude'] ?? $request->input('longitude') ?? -122.4194);

        // Broadcast searching status to dashboard
        event(new DispatchUpdated($tenant->id, [
            'type' => 'searching',
            'message' => "Processing incoming call from {$customerPhone}...",
        ]));

        // 4. Parse Requested Time
        try {
            $requestedTimeCarbon = Carbon::parse($requestedTime);
        } catch (\Exception $e) {
            $invalidTimeResult = [
                'status' => 'error',
                'message' => 'Invalid requested_time format.',
            ];

            event(new DispatchUpdated($tenant->id, [
                'type' => 'error',
                'message' => 'Invalid requested_time format.',
            ]));

            if ($toolCallId) {
                return response()->json([
                    'results' => [
                        [
                            'toolCallId' => $toolCallId,
                            'result' => $invalidTimeResult,
                        ],
                    ],
                ], 400);
            }

            return response()->json($invalidTimeResult, 400);
        }

        $dayOfWeek = $requestedTimeCarbon->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $timeOnly = $requestedTimeCarbon->format('H:i:s');

        // 5. Match Employees by Skill and shift availability
        $employees = Employee::get()->filter(function ($employee) use ($serviceType, $dayOfWeek, $timeOnly, $requiredCert) {
            $hasSkill = is_array($employee->skills) && in_array($serviceType, $employee->skills);
            if (! $hasSkill) {
                return false;
            }

            // Check hard constraint certifications
            if ($requiredCert !== null) {
                $hasCert = is_array($employee->certifications) && in_array($requiredCert, $employee->certifications);
                if (! $hasCert) {
                    return false;
                }
            }

            $isAvailable = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $timeOnly)
                ->where('end_time', '>=', $timeOnly)
                ->exists();

            return $isAvailable;
        });

        $candidates = [];
        foreach ($employees as $employee) {
            $techLat = $employee->latitude ?? 37.7749;
            $techLng = $employee->longitude ?? -122.4194;
            $distance = sqrt(pow($techLat - $jobLat, 2) + pow($techLng - $jobLng, 2)) * 111.0;
            $theta = 1.0 - ($distance / 50.0);
            $theta = max(0.0, min(1.0, $theta));

            if ($priorityState === 'emergency') {
                $candidates[] = [
                    'employee' => $employee,
                    'theta' => $theta,
                ];
            } else {
                $bufferMinutes = 90;
                $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
                $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

                $hasOverlap = Booking::where('employee_id', $employee->id)
                    ->where('status', 'booked')
                    ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                    ->exists();

                if (! $hasOverlap) {
                    $candidates[] = [
                        'employee' => $employee,
                        'theta' => $theta,
                    ];
                }
            }
        }

        // Sort by theta compatibility
        usort($candidates, function ($a, $b) {
            return $b['theta'] <=> $a['theta'];
        });

        $assignedEmployee = count($candidates) > 0 ? $candidates[0]['employee'] : null;

        // 6. Handle Schedule Conflicts or Failures
        if (! $assignedEmployee) {
            $conflictMessage = "No available technician found with skill '{$serviceType}' at the requested time. Routing call to voicemail fallback.";

            event(new DispatchUpdated($tenant->id, [
                'type' => 'error',
                'message' => $conflictMessage,
            ]));

            $callId = $request->input('call_id')
                ?? $request->input('call.id')
                ?? $request->input('message.call.id')
                ?? $request->input('message.callId');

            if ($callId) {
                $callLog = CallLog::where('call_id', $callId)->first();
                if ($callLog) {
                    $callLog->update([
                        'call_end_reason' => 'forwarded_to_voicemail',
                    ]);
                }
            }

            $voicemailResult = [
                'status' => 'forward_to_voicemail',
                'action' => 'transfer',
                'destination' => '+18005550199',
                'message' => $conflictMessage,
            ];

            if ($toolCallId) {
                return response()->json([
                    'results' => [
                        [
                            'toolCallId' => $toolCallId,
                            'result' => $voicemailResult,
                        ],
                    ],
                ]);
            }

            return response()->json($voicemailResult, 422);
        }

        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $assignedEmployee->id,
            'customer_phone' => $customerPhone,
            'job_details' => "Automated AI dispatch for {$serviceType}",
            'status' => 'booked',
            'scheduled_start' => $requestedTimeCarbon,
            'priority_state' => $priorityState,
            'required_certification' => $requiredCert,
            'latitude' => $jobLat,
            'longitude' => $jobLng,
        ]);

        $callId = $request->input('call_id')
            ?? $request->input('call.id')
            ?? $request->input('message.call.id')
            ?? $request->input('message.callId');

        if ($callId) {
            Cache::put("call_booking_map:{$callId}", $booking->id, 86400); // 1 day cache
        }

        if ($priorityState === 'emergency') {
            $rebalancer = app(DispatchRebalancerService::class);
            $rebalancer->rebalance($booking, $assignedEmployee);
        } else {
            SendTechnicianAlertJob::dispatch($booking);
        }

        $successMessage = "Appointment booked successfully with {$assignedEmployee->first_name} {$assignedEmployee->last_name} at {$requestedTimeCarbon->format('Y-m-d H:i')}.";

        event(new DispatchUpdated($tenant->id, [
            'type' => 'success',
            'message' => $successMessage,
            'booking' => $booking->load('employee'),
        ]));

        $successResult = [
            'status' => 'success',
            'message' => $successMessage,
            'booking_id' => $booking->id,
            'employee_name' => "{$assignedEmployee->first_name} {$assignedEmployee->last_name}",
            'scheduled_time' => $requestedTimeCarbon->toDateTimeString(),
        ];

        if ($toolCallId) {
            return response()->json([
                'results' => [
                    [
                        'toolCallId' => $toolCallId,
                        'result' => $successResult,
                    ],
                ],
            ]);
        }

        return response()->json($successResult);
    }
}
