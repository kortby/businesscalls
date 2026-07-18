<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictToTelephonyIps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $allowlist = config('telephony.allowlist', [
            '100.20.5.228',
            '167.150.224.0/23',
            '127.0.0.1',
            '::1',
        ]);

        $clientIp = $request->ip();

        foreach ($allowlist as $allowed) {
            if ($this->ipInCidr($clientIp, $allowed)) {
                return $next($request);
            }
        }

        // Fallback bearer authorization header check using custom client credentials
        $fallbackToken = config('telephony.client_credentials');
        $bearerToken = $request->bearerToken();

        if ($fallbackToken && $bearerToken && hash_equals((string) $fallbackToken, (string) $bearerToken)) {
            return $next($request);
        }

        Log::warning("RestrictToTelephonyIps: Unauthorized IP '{$clientIp}' rejected.");

        return response()->json(['error' => 'Forbidden: Unauthorized IP address.'], 403);
    }

    /**
     * Check if an IP address is within a CIDR range.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $binIp = str_pad(decbin($ipLong), 32, '0', STR_PAD_LEFT);
        $binSub = str_pad(decbin($subnetLong), 32, '0', STR_PAD_LEFT);

        return substr($binIp, 0, $mask) === substr($binSub, 0, $mask);
    }
}
