<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IvrController extends Controller
{
    /**
     * Handle incoming IVR digit presses / DTMF tones.
     */
    public function handle(Request $request, ?string $tenant_id = null): JsonResponse
    {
        // 1. Resolve Tenant ID
        $resolvedTenantId = $tenant_id
            ?? $request->query('tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->input('tenant_slug');

        if (! $resolvedTenantId) {
            return response()->json(['error' => 'Tenant ID is required.'], 400);
        }

        $tenant = Tenant::where('id', $resolvedTenantId)
            ->orWhere('slug', $resolvedTenantId)
            ->first();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        // Apply tenant scope context
        TenantScope::setTenantId($tenant->id);

        // 2. Extract digit/DTMF, call ID and tool call ID
        $callId = $request->input('call_id')
            ?? $request->input('call.id')
            ?? $request->input('message.call.id')
            ?? 'unknown_call';

        $digit = $request->input('digit')
            ?? $request->input('dtmf')
            ?? $request->input('key')
            ?? $request->input('message.toolCalls.0.function.arguments.digit')
            ?? $request->input('message.toolCall.function.arguments.digit');

        $toolCallId = $request->input('message.toolCalls.0.id')
            ?? $request->input('message.toolCall.id');

        if ($digit === null) {
            return response()->json(['error' => 'Digit / DTMF payload is missing.'], 400);
        }

        // 3. Track digit sequence in cache
        $cacheKey = "ivr_sequence_{$callId}";
        $digits = Cache::get($cacheKey, '');
        $digits .= trim($digit);

        // Save sequence in cache (30 minutes TTL)
        Cache::put($cacheKey, $digits, now()->addMinutes(30));

        // 4. Fetch IVR routes and detection delay from tenant settings
        $detectionDelayMs = (int) $tenant->getSetting('ivr_detection_delay_ms', 500);
        $ivrRoutes = $tenant->getSetting('ivr_routes', [
            '1' => ['action' => 'transfer', 'agent_id' => 'agent_spanish'],
            '2' => ['action' => 'submenu', 'menu' => 'billing'],
            '21' => ['action' => 'transfer', 'agent_id' => 'agent_csat'],
            '22' => ['action' => 'transfer', 'agent_id' => 'agent_payment'],
        ]);

        // 5. Match route
        $route = $ivrRoutes[$digits] ?? null;

        if ($route) {
            if ($route['action'] === 'transfer') {
                // Clear the cache sequence as we are transferring out
                Cache::forget($cacheKey);
            }

            $resultData = [
                'success' => true,
                'digits_pressed' => $digits,
                'action' => $route['action'],
                'destination_agent_id' => $route['agent_id'] ?? null,
                'menu' => $route['menu'] ?? null,
                'detection_delay_ms' => $detectionDelayMs,
            ];

            if ($toolCallId) {
                return response()->json([
                    'results' => [
                        [
                            'toolCallId' => $toolCallId,
                            'result' => $resultData,
                        ],
                    ],
                ]);
            }

            return response()->json($resultData);
        }

        // If no match yet but there are routes starting with the sequence, keep waiting
        $hasPotentialMatch = false;
        foreach (array_keys($ivrRoutes) as $key) {
            if (str_starts_with((string) $key, $digits)) {
                $hasPotentialMatch = true;
                break;
            }
        }

        if (! $hasPotentialMatch) {
            // No potential match, reset digits sequence
            Cache::forget($cacheKey);

            $resultData = [
                'success' => true,
                'digits_pressed' => $digits,
                'action' => 'none',
                'detection_delay_ms' => $detectionDelayMs,
            ];

            if ($toolCallId) {
                return response()->json([
                    'results' => [
                        [
                            'toolCallId' => $toolCallId,
                            'result' => $resultData,
                        ],
                    ],
                ]);
            }

            return response()->json($resultData);
        }

        $resultData = [
            'success' => true,
            'digits_pressed' => $digits,
            'action' => 'collecting',
            'detection_delay_ms' => $detectionDelayMs,
        ];

        if ($toolCallId) {
            return response()->json([
                'results' => [
                    [
                        'toolCallId' => $toolCallId,
                        'result' => $resultData,
                    ],
                ],
            ]);
        }

        return response()->json($resultData);
    }
}
