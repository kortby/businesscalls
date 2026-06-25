<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspendedTenantCalls
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantIdOrSlug = $request->input('tenant_id')
            ?? $request->input('tenant_slug')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_id')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_slug')
            ?? $request->route('tenant_id')
            ?? $request->route('tenant_slug')
            ?? $request->header('X-Tenant-ID')
            ?? $request->header('x-tenant-id');

        if ($tenantIdOrSlug) {
            $tenant = Tenant::where('id', $tenantIdOrSlug)
                ->orWhere('slug', $tenantIdOrSlug)
                ->first();

            if ($tenant) {
                $spend = $tenant->calculateSpendUsage();
                $limit = $tenant->getSpendLimit();

                if ($spend > $limit) {
                    // Lock voice assistant active flag in settings
                    $settings = $tenant->settings ?? [];
                    $settings['voice_assistant_active'] = false;
                    $tenant->settings = $settings;
                    $tenant->save();

                    return response()->json([
                        'error' => 'Payment Required',
                        'message' => 'Tenant API spend limit exceeded. Inbound call routing is suspended.',
                    ], 402);
                }
            }
        }

        return $next($request);
    }
}
