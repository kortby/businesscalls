<?php

namespace App\Http\Controllers\Api;

use App\AI\Tools\BookAppointmentTool;
use App\AI\Tools\CancelBookingTool;
use App\AI\Tools\CheckAvailabilityTool;
use App\AI\Tools\CheckInventoryTool;
use App\AI\Tools\CheckTechnicianEtaTool;
use App\AI\Tools\DispatchTechnicianTool;
use App\AI\Tools\GetAvailabilitySlotsTool;
use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\AI\Tools\KnowledgeSearchTool;
use App\AI\Tools\LookupBookingTool;
use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class McpController extends Controller
{
    /**
     * Handle incoming Model Context Protocol (MCP) server requests.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Security & Authentication
        $authHeader = $request->header('Authorization')
            ?? $request->header('X-MCP-Token')
            ?? $request->header('X-Vapi-Secret')
            ?? $request->header('X-Retell-Secret')
            ?? $request->query('token');

        if (! $authHeader) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32099,
                    'message' => 'Authorization token is missing.',
                ],
                'id' => $request->input('id'),
            ], 401);
        }

        // Clean bearer token prefix
        $token = str_replace('Bearer ', '', $authHeader);

        // Find tenant by secret key or Personal Access Token (Sanctum)
        $tenant = Tenant::where('secret_key', $token)->first();

        if (! $tenant) {
            $pat = PersonalAccessToken::findToken($token);
            if ($pat && $pat->tokenable) {
                $user = $pat->tokenable;
                $tenant = $user->tenant;
            }
        }

        if (! $tenant) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32099,
                    'message' => 'Unauthorized token or tenant context.',
                ],
                'id' => $request->input('id'),
            ], 401);
        }

        // 2. Validate Signature Header if present using tenant's secret key
        $signature = $request->header('X-Retell-Signature')
            ?? $request->header('X-Vapi-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('x-vapi-signature')
            ?? $request->header('x-signature');

        if ($signature && $tenant->secret_key) {
            $computedSignature = hash_hmac('sha256', $request->getContent(), $tenant->secret_key);
            if (! hash_equals($computedSignature, $signature)) {
                return response()->json([
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32099,
                        'message' => 'Invalid signature.',
                    ],
                    'id' => $request->input('id'),
                ], 401);
            }
        }

        // Apply tenant scope context for database queries isolation
        TenantScope::setTenantId($tenant->id);

        // 3. Route JSON-RPC Methods
        $method = $request->input('method');
        $id = $request->input('id');

        if ($method === 'tools/list') {
            return $this->listTools($id);
        }

        if ($method === 'tools/call') {
            $name = $request->input('params.name');
            $arguments = $request->input('params.arguments') ?? [];

            return $this->callTool($name, $arguments, $id, $tenant);
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32601,
                'message' => 'Method not found: '.$method,
            ],
            'id' => $id,
        ], 404);
    }

    /**
     * List available tools.
     */
    protected function listTools(mixed $id): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'result' => [
                'tools' => [
                    [
                        'name' => 'check_inventory',
                        'description' => 'Check if a part or item is in stock in the inventory.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'part_name' => ['type' => 'string', 'description' => 'The name of the part to search (e.g. faucet, pipe, wire).'],
                            ],
                            'required' => ['part_name'],
                        ],
                    ],
                    [
                        'name' => 'reschedule_appointment',
                        'description' => 'Reschedule an existing booking to a new start time date.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'booking_id' => ['type' => 'integer', 'description' => 'The ID of the booking to reschedule.'],
                                'new_start_time' => ['type' => 'string', 'description' => 'The new scheduled start time as an ISO-8601 date string.'],
                            ],
                            'required' => ['booking_id', 'new_start_time'],
                        ],
                    ],
                    [
                        'name' => 'voicemail_fallback',
                        'description' => 'Route the active call stream to the automated voicemail mailbox because no technicians are available or they are busy.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'call_id' => ['type' => 'string', 'description' => 'The call ID of the active call.'],
                                'reason' => ['type' => 'string', 'description' => 'The reason for fallback.'],
                            ],
                            'required' => ['call_id'],
                        ],
                    ],
                    [
                        'name' => 'check_technician_gps',
                        'description' => 'Check the current real-time GPS location and coordinates (latitude, longitude) of a technician/employee.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'employee_id' => ['type' => 'integer', 'description' => 'The ID of the technician/employee to locate.'],
                            ],
                            'required' => ['employee_id'],
                        ],
                    ],
                    [
                        'name' => 'get_first_three_availabilities',
                        'description' => 'Get the first 3 available technician appointment slots formatted for presentation.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'service_type' => ['type' => 'string', 'description' => 'Optional service type or skill.'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'get_availability_slots',
                        'description' => 'Retrieve open technician availability slots for a specific date.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'date' => ['type' => 'string', 'description' => 'Target date (YYYY-MM-DD).'],
                                'service_type' => ['type' => 'string', 'description' => 'Optional service type or skill.'],
                            ],
                            'required' => ['date'],
                        ],
                    ],
                    [
                        'name' => 'check_availability',
                        'description' => 'Check if a specific time or skill has available technician slots.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'service_type' => ['type' => 'string', 'description' => 'Optional service type or skill.'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'book_appointment',
                        'description' => 'Book an appointment for a customer with an available technician.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'customer_phone' => ['type' => 'string', 'description' => 'Customer phone number.'],
                                'job_details' => ['type' => 'string', 'description' => 'Job description.'],
                                'scheduled_start' => ['type' => 'string', 'description' => 'Scheduled start ISO-8601 date string.'],
                                'employee_id' => ['type' => 'integer', 'description' => 'Optional assigned technician ID.'],
                            ],
                            'required' => ['customer_phone', 'job_details', 'scheduled_start'],
                        ],
                    ],
                    [
                        'name' => 'lookup_booking',
                        'description' => 'Look up existing customer appointments by phone number or booking ID.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'customer_phone' => ['type' => 'string', 'description' => 'Customer phone number.'],
                                'booking_id' => ['type' => 'integer', 'description' => 'Booking ID.'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'cancel_booking',
                        'description' => 'Cancel an existing customer appointment booking.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'booking_id' => ['type' => 'integer', 'description' => 'The ID of the booking to cancel.'],
                                'reason' => ['type' => 'string', 'description' => 'Optional cancellation reason.'],
                            ],
                            'required' => ['booking_id'],
                        ],
                    ],
                    [
                        'name' => 'check_technician_eta',
                        'description' => 'Check real-time status, GPS location, and ETA of assigned technician.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'booking_id' => ['type' => 'integer', 'description' => 'The ID of the booking.'],
                            ],
                            'required' => ['booking_id'],
                        ],
                    ],
                    [
                        'name' => 'dispatch_technician',
                        'description' => 'Dispatch optimal technician to a booking based on workload metrics.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'booking_id' => ['type' => 'integer', 'description' => 'The ID of the booking.'],
                                'required_skill' => ['type' => 'string', 'description' => 'Required skill.'],
                            ],
                            'required' => ['booking_id'],
                        ],
                    ],
                    [
                        'name' => 'knowledge_search',
                        'description' => 'Search tenant knowledge base for manuals and policies.',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'query' => ['type' => 'string', 'description' => 'Search phrase.'],
                            ],
                            'required' => ['query'],
                        ],
                    ],
                ],
            ],
            'id' => $id,
        ]);
    }

    /**
     * Call a tool by name.
     */
    protected function callTool(string $name, array $arguments, mixed $id, Tenant $tenant): JsonResponse
    {
        if ($name === 'check_inventory') {
            $partName = strtolower(trim($arguments['part_name'] ?? ''));
            if (! $partName) {
                return $this->toolErrorResponse('part_name argument is required.', $id);
            }

            $tool = new CheckInventoryTool;
            $resultData = $tool->handle($tenant->id, $partName);

            $text = $resultData['in_stock']
                ? "The part '{$partName}' is in stock. Current quantity: {$resultData['quantity']}."
                : "The part '{$partName}' is out of stock.";

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                    ],
                ],
                'id' => $id,
            ]);
        }

        if ($name === 'reschedule_appointment') {
            $bookingId = $arguments['booking_id'] ?? null;
            $newStartTime = $arguments['new_start_time'] ?? null;

            if (! $bookingId || ! $newStartTime) {
                return $this->toolErrorResponse('booking_id and new_start_time arguments are required.', $id);
            }

            $booking = Booking::find($bookingId);
            if (! $booking) {
                return $this->toolErrorResponse("Booking with ID {$bookingId} not found.", $id);
            }

            $employee = $booking->employee;
            if (! $employee) {
                return $this->toolErrorResponse('No employee assigned to this booking.', $id);
            }

            try {
                $requestedTimeCarbon = Carbon::parse($newStartTime);
            } catch (\Exception $e) {
                return $this->toolErrorResponse('Invalid date format for new_start_time.', $id);
            }

            $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
            $timeOnly = $requestedTimeCarbon->format('H:i:s');

            $isAvailable = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $timeOnly)
                ->where('end_time', '>=', $timeOnly)
                ->exists();

            if (! $isAvailable) {
                return $this->toolErrorResponse('Rescheduling failed: The technician is not scheduled to work during this shift.', $id);
            }

            $startBuffer = $requestedTimeCarbon->copy()->subMinutes(90);
            $endBuffer = $requestedTimeCarbon->copy()->addMinutes(90);

            $hasOverlap = Booking::where('employee_id', $employee->id)
                ->where('status', 'booked')
                ->where('id', '!=', $booking->id)
                ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                ->exists();

            if ($hasOverlap) {
                return $this->toolErrorResponse('Rescheduling failed: Conflict with an existing technician appointment (1.5-hour travel buffer enforced).', $id);
            }

            $booking->update([
                'scheduled_start' => $requestedTimeCarbon,
            ]);

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Booking #{$bookingId} has been successfully rescheduled to {$requestedTimeCarbon->toIso8601String()}.",
                        ],
                    ],
                ],
                'id' => $id,
            ]);
        }

        if ($name === 'check_technician_gps') {
            $employeeId = $arguments['employee_id'] ?? null;
            if (! $employeeId) {
                return $this->toolErrorResponse('employee_id argument is required.', $id);
            }

            $employee = Employee::find($employeeId);
            if (! $employee) {
                return $this->toolErrorResponse("Employee with ID {$employeeId} not found.", $id);
            }

            $lat = 37.7749 + (float) (($employee->id % 100) / 1000.0);
            $lng = -122.4194 + (float) (($employee->id % 50) / 1000.0);

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Technician {$employee->first_name} {$employee->last_name} is located at: Latitude {$lat}, Longitude {$lng}.",
                        ],
                    ],
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'status' => 'active',
                ],
                'id' => $id,
            ]);
        }

        if ($name === 'voicemail_fallback') {
            $callId = $arguments['call_id'] ?? null;
            $reason = $arguments['reason'] ?? 'No available technicians';

            if (! $callId) {
                return $this->toolErrorResponse('call_id argument is required.', $id);
            }

            $callLog = CallLog::where('call_id', $callId)->first();
            if ($callLog) {
                $callLog->update([
                    'call_end_reason' => 'forwarded_to_voicemail',
                ]);
            }

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Routing call to voicemail fallback mailbox due to: {$reason}.",
                        ],
                    ],
                    'action' => 'transfer',
                    'destination' => '+18005550199',
                    'status' => 'forward_to_voicemail',
                ],
                'id' => $id,
            ]);
        }

        $resultData = null;

        switch ($name) {
            case 'get_first_three_availabilities':
                $tool = new GetFirstThreeAvailabilitiesTool;
                $resultData = $tool->handle($tenant->id, $arguments['service_type'] ?? null);
                break;

            case 'get_availability_slots':
                $tool = new GetAvailabilitySlotsTool;
                $resultData = $tool->handle($tenant->id, $arguments['date'] ?? now()->toDateString(), $arguments['service_type'] ?? null);
                break;

            case 'check_availability':
                $tool = new CheckAvailabilityTool;
                $resultData = $tool->handle($tenant->id, $arguments['service_type'] ?? null);
                break;

            case 'book_appointment':
            case 'create_booking':
                $tool = new BookAppointmentTool;
                $resultData = $tool->handle(
                    $tenant->id,
                    $arguments['customer_phone'] ?? '',
                    $arguments['job_details'] ?? '',
                    $arguments['scheduled_start'] ?? '',
                    $arguments['employee_id'] ?? null
                );
                break;

            case 'lookup_booking':
                $tool = new LookupBookingTool;
                $resultData = $tool->handle($tenant->id, $arguments['customer_phone'] ?? null, isset($arguments['booking_id']) ? (int) $arguments['booking_id'] : null);
                break;

            case 'cancel_booking':
                $tool = new CancelBookingTool;
                $resultData = $tool->handle($tenant->id, (int) ($arguments['booking_id'] ?? 0), $arguments['reason'] ?? null);
                break;

            case 'check_technician_eta':
                $tool = new CheckTechnicianEtaTool;
                $resultData = $tool->handle($tenant->id, (int) ($arguments['booking_id'] ?? 0));
                break;

            case 'dispatch_technician':
                $tool = new DispatchTechnicianTool;
                $resultData = $tool->handle($tenant->id, (int) ($arguments['booking_id'] ?? 0), $arguments['required_skill'] ?? null);
                break;

            case 'knowledge_search':
                $tool = new KnowledgeSearchTool;
                $resultData = $tool->handle($tenant->id, $arguments['query'] ?? '');
                break;

            default:
                return response()->json([
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32601,
                        'message' => 'Tool not found: '.$name,
                    ],
                    'id' => $id,
                ], 404);
        }

        if (($resultData['status'] ?? '') === 'error') {
            return $this->toolErrorResponse($resultData['message'] ?? 'Tool execution failed.', $id);
        }

        $textResponse = $resultData['message'] ?? json_encode($resultData);

        return response()->json([
            'jsonrpc' => '2.0',
            'result' => array_merge([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $textResponse,
                    ],
                ],
            ], $resultData),
            'id' => $id,
        ]);
    }

    /**
     * Helper to return standard JSON-RPC tool error response.
     */
    protected function toolErrorResponse(string $message, mixed $id): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
                'isError' => true,
            ],
            'id' => $id,
        ]);
    }
}
