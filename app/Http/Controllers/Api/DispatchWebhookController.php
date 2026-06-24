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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $customerPhone = $arguments['customer_phone'] ?? $request->input('customer_phone');
        $serviceType = $arguments['service_type'] ?? $request->input('service_type');
        $requestedTime = $arguments['requested_time'] ?? $request->input('requested_time');
        $tenantIdOrSlug = $arguments['tenant_id'] ?? $request->input('tenant_id') ?? $arguments['tenant_slug'] ?? $request->input('tenant_slug') ?? $request->input('tenant_id');

        if (! $customerPhone || ! $serviceType || ! $requestedTime || ! $tenantIdOrSlug) {
            return response()->json([
                'error' => 'Missing required fields: customer_phone, service_type, requested_time, and tenant_id/slug must be provided.',
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

        // 5. Match Employees by Skill Trade Category
        $employees = Employee::get()->filter(function ($employee) use ($serviceType) {
            return is_array($employee->skills) && in_array($serviceType, $employee->skills);
        });

        $assignedEmployee = null;

        foreach ($employees as $employee) {
            // Check shift availability
            $isAvailable = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $timeOnly)
                ->where('end_time', '>=', $timeOnly)
                ->exists();

            if (! $isAvailable) {
                continue;
            }

            // Check bookings schedule collision with a 1.5-hour (90 minutes) buffer
            $bufferMinutes = 90;
            $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
            $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

            $hasOverlap = Booking::where('employee_id', $employee->id)
                ->where('status', 'booked')
                ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                ->exists();

            if (! $hasOverlap) {
                $assignedEmployee = $employee;
                break;
            }
        }

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
        ]);

        SendTechnicianAlertJob::dispatch($booking);

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
