<?php

namespace App\Http\Controllers\Api;

use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityWebhookController extends Controller
{
    /**
     * Handle incoming AI request for the first 3 availability options for booking a technician.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Parse parameters (supporting flat and nested payload structures)
        $toolCallId = $request->input('message.toolCalls.0.id');
        $arguments = $request->input('message.toolCalls.0.function.arguments', []);
        if (is_string($arguments)) {
            $arguments = json_decode($arguments, true) ?? [];
        }

        $tenantIdOrSlug = $arguments['tenant_id']
            ?? $request->input('tenant_id')
            ?? $arguments['tenant_slug']
            ?? $request->input('tenant_slug')
            ?? $request->input('message.tenantId')
            ?? $request->route('tenant_id');

        if (! $tenantIdOrSlug) {
            $dialedNumber = $request->input('message.phoneNumber.number')
                ?? $request->input('message.phone.number')
                ?? $request->input('phoneNumber');

            if ($dialedNumber) {
                $tenant = Tenant::where('settings->telephony_phone_number', $dialedNumber)->first();
                if ($tenant) {
                    $tenantIdOrSlug = $tenant->id;
                }
            }
        }

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

        TenantScope::setTenantId($tenant->id);

        $serviceTypeInput = trim($arguments['service_type']
            ?? $arguments['serviceType']
            ?? $request->input('service_type')
            ?? $request->input('serviceType')
            ?? '');

        // 3. Delegate to native GetFirstThreeAvailabilitiesTool
        $tool = new GetFirstThreeAvailabilitiesTool;
        $resultData = $tool->handle($tenant->id, $serviceTypeInput ?: null);

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
