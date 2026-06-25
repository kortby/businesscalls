<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TrafficRouterService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrafficRouterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = null;

        if ($user && $user->tenant) {
            $tenant = $user->tenant;
        } else {
            $tenantIdOrSlug = $request->input('tenant_id')
                ?? $request->input('tenant_slug')
                ?? $request->route('tenant_id')
                ?? $request->route('tenant_slug')
                ?? $request->header('X-Tenant-ID');

            if ($tenantIdOrSlug) {
                $tenant = Tenant::where('id', $tenantIdOrSlug)
                    ->orWhere('slug', $tenantIdOrSlug)
                    ->first();
            }
        }

        if ($tenant) {
            $router = app(TrafficRouterService::class);
            $variant = $router->route($tenant);

            if ($variant) {
                $request->attributes->set('active_experiment_variant', $variant);
            }
        }

        return $next($request);
    }
}
