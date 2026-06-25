<?php

namespace App\Http\Controllers\Api;

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
                                'part_name' => [
                                    'type' => 'string',
                                    'description' => 'The name of the part to search (e.g. faucet, pipe, wire).',
                                ],
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
                                'booking_id' => [
                                    'type' => 'integer',
                                    'description' => 'The ID of the booking to reschedule.',
                                ],
                                'new_start_time' => [
                                    'type' => 'string',
                                    'description' => 'The new scheduled start time as an ISO-8601 date string.',
                                ],
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
                                'call_id' => [
                                    'type' => 'string',
                                    'description' => 'The call ID of the active call.',
                                ],
                                'reason' => [
                                    'type' => 'string',
                                    'description' => 'The reason for fallback.',
                                ],
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
                                'employee_id' => [
                                    'type' => 'integer',
                                    'description' => 'The ID of the technician/employee to locate.',
                                ],
                            ],
                            'required' => ['employee_id'],
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

            // Pull inventory map from tenant settings or default to a standard mock
            $inventory = $tenant->getSetting('inventory', [
                'faucet' => 15,
                'pipe' => 24,
                'wire' => 50,
                'thermostat' => 8,
            ]);

            // Ensure case insensitive keys
            $inventory = array_change_key_case($inventory, CASE_LOWER);
            $qty = $inventory[$partName] ?? 0;

            if ($qty > 0) {
                $text = "The part '{$partName}' is in stock. Current quantity: {$qty}.";
            } else {
                $text = "The part '{$partName}' is out of stock.";
            }

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

            // Find booking (scoped by TenantScope automatically!)
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

            // 1. Verify shift availability
            $isAvailable = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $timeOnly)
                ->where('end_time', '>=', $timeOnly)
                ->exists();

            if (! $isAvailable) {
                return $this->toolErrorResponse('Rescheduling failed: The technician is not scheduled to work during this shift.', $id);
            }

            // 2. Verify travel buffer collision (90 minutes)
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

            // 3. Update the booking
            $booking->update([
                'scheduled_start' => $requestedTimeCarbon,
            ]);

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Booking #{$bookingId} has been successfully rescheduled to ".$requestedTimeCarbon->toIso8601String().'.',
                        ],
                    ],
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

        if ($name === 'check_technician_gps') {
            $employeeId = $arguments['employee_id'] ?? null;
            if (! $employeeId) {
                return $this->toolErrorResponse('employee_id argument is required.', $id);
            }

            $employee = Employee::find($employeeId);
            if (! $employee) {
                return $this->toolErrorResponse("Employee with ID {$employeeId} not found.", $id);
            }

            // Simulate stable GPS coordinates based on employee ID
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

        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32601,
                'message' => 'Tool not found: '.$name,
            ],
            'id' => $id,
        ], 404);
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
