<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleTenantTelephony
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Identify tenant context
        $tenantId = $request->user()?->tenant_id
            ?? $request->route('tenant_id')
            ?? $request->input('tenant_id')
            ?? 'default';

        // Calculate a signature based on the IP address, tenant identity, and user agent
        $signature = sha1(
            $request->ip().'|'.
            $tenantId.'|'.
            $request->header('User-Agent')
        );

        $key = 'telephony-throttle:'.$tenantId.':'.$signature;

        // Allow at most 5 programmatic call triggers per minute per tenant/signature
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Too many call triggers. Rate limit exceeded for this tenant.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
