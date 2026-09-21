<?php

namespace App\Http\Controllers\Api;

use App\AI\Tools\GetFirstThreeAvailabilitiesTool;
use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            ?? $request->query('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? $request->header('x-tenant-id')
            ?? $arguments['tenant_slug']
            ?? $request->input('tenant_slug')
            ?? $request->query('tenant_slug')
            ?? $request->input('message.tenantId')
            ?? $request->route('tenant_id');

        if (! $tenantIdOrSlug) {
            $dialedNumber = $request->input('message.phoneNumber.number')
                ?? $request->input('message.phone.number')
                ?? $request->input('message.call.phoneNumber.number')
                ?? $request->input('message.call.phone.number')
                ?? $request->input('phoneNumber');

            if ($dialedNumber) {
                $cleanDialed = preg_replace('/[^\d+]/', '', (string) $dialedNumber);
                $matchedTenant = Tenant::get()->first(function ($t) use ($cleanDialed) {
                    $settings = $t->settings ?? [];
                    $phone1 = preg_replace('/[^\d+]/', '', (string) ($settings['telephony_phone_number'] ?? ''));
                    $phone2 = preg_replace('/[^\d+]/', '', (string) ($settings['phone_number'] ?? ''));
                    $phone3 = preg_replace('/[^\d+]/', '', (string) ($settings['sms_number'] ?? ''));
                    $mappings = array_map(fn ($k) => preg_replace('/[^\d+]/', '', (string) $k), array_keys($settings['phone_mappings'] ?? []));

                    return ($phone1 && ($phone1 === $cleanDialed || str_ends_with($cleanDialed, substr($phone1, -10))))
                        || ($phone2 && ($phone2 === $cleanDialed || str_ends_with($cleanDialed, substr($phone2, -10))))
                        || ($phone3 && ($phone3 === $cleanDialed || str_ends_with($cleanDialed, substr($phone3, -10))))
                        || in_array($cleanDialed, $mappings);
                });

                if ($matchedTenant) {
                    $tenantIdOrSlug = $matchedTenant->id;
                }
            }
        }

        if (! $tenantIdOrSlug && Tenant::count() === 1) {
            $tenantIdOrSlug = Tenant::first()->id;
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
        try {
            $tool = new GetFirstThreeAvailabilitiesTool;
            $resultData = $tool->handle($tenant->id, $serviceTypeInput ?: null);
        } catch (\Throwable $e) {
            Log::error('AvailabilityWebhookController error: '.$e->getMessage(), ['exception' => $e]);
            $resultData = [
                'status' => 'error',
                'message' => 'Unable to query availability slots at this moment.',
            ];
        }

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
