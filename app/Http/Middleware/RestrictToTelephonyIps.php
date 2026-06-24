<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToTelephonyIps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = config('telephony.allowlist', [
            '100.20.5.228',
            '127.0.0.1',
            '::1',
        ]);

        $clientIp = $request->ip();

        if (in_array($clientIp, $allowlist)) {
            return $next($request);
        }

        // Fallback bearer authorization header check using custom client credentials
        $fallbackToken = config('telephony.client_credentials');
        $bearerToken = $request->bearerToken();

        if ($fallbackToken && $bearerToken && hash_equals((string) $fallbackToken, (string) $bearerToken)) {
            return $next($request);
        }

        return response()->json(['error' => 'Forbidden: Unauthorized IP address.'], 403);
    }
}
