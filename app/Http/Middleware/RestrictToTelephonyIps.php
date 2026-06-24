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
        $allowlist = [
            '100.20.5.228',
            '127.0.0.1',
            '::1',
        ];

        $clientIp = $request->ip();

        if (! in_array($clientIp, $allowlist)) {
            return response()->json(['error' => 'Forbidden: Unauthorized IP address.'], 403);
        }

        return $next($request);
    }
}
