<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantOAuthToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WebhookGatewayMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('x-vapi-signature') ?? $request->header('x-signature') ?? $request->input('signature');

        // Resolve tenant ID or slug
        $tenantIdOrSlug = $request->input('tenant_id')
            ?? $request->input('tenant_slug')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_id')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_slug')
            ?? $request->route('tenant_id')
            ?? $request->route('tenant_slug');

        if (! $tenantIdOrSlug) {
            return $next($request);
        }

        // 1. Database session caching for Tenant model settings lookup
        $cacheKey = 'tenant-session:'.$tenantIdOrSlug;

        $tenant = Cache::remember($cacheKey, 600, function () use ($tenantIdOrSlug) {
            return Tenant::where('id', $tenantIdOrSlug)
                ->orWhere('slug', $tenantIdOrSlug)
                ->first();
        });

        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        // Extend the session context TTL directly using Cache::touch()
        Cache::touch($cacheKey, 600);

        $vapiSecret = $request->header('X-Vapi-Secret') ?? $request->header('x-vapi-secret');
        $retellSecret = $request->header('X-Retell-Secret') ?? $request->header('x-retell-secret');
        $authToken = $request->bearerToken();

        $hasCustomCredentials = false;
        if ($tenant->secret_key) {
            $hasCustomCredentials = ($authToken && hash_equals($tenant->secret_key, $authToken))
                || ($vapiSecret && hash_equals($tenant->secret_key, $vapiSecret))
                || ($retellSecret && hash_equals($tenant->secret_key, $retellSecret));
        }

        if (! $hasCustomCredentials && $authToken) {
            $tokenRecord = TenantOAuthToken::where('access_token', $authToken)->first();
            if ($tokenRecord && $tokenRecord->tenant_id === $tenant->id && ! $tokenRecord->expires_at->isPast()) {
                $hasCustomCredentials = true;
            }
        }

        // 2. Signature Validation and Replay Attack Prevention
        if ($tenant->secret_key && ! $hasCustomCredentials) {
            if (! $signature) {
                return response()->json(['error' => 'Authentication missing (Token or Signature).'], 401);
            }

            $sigKey = 'webhook-sig:'.sha1($signature);

            if (Cache::has($sigKey)) {
                // If this signature was recently processed, extend its rate-limiting replay window
                Cache::touch($sigKey, 120);

                return response()->json(['error' => 'Duplicate request detected.'], 429);
            }

            $computed = hash_hmac('sha256', $request->getContent(), $tenant->secret_key);
            if (! hash_equals($computed, $signature)) {
                return response()->json(['error' => 'HMAC verification failed.'], 401);
            }

            // Cache signature to prevent immediate duplicate execution
            Cache::put($sigKey, true, 120);
        }

        // 3. Rate-limiting lookups
        $rateLimitKey = 'webhook-rate:'.$tenant->id;
        $attempts = (int) Cache::get($rateLimitKey, 0);

        if ($attempts >= 10) {
            // Touch key to extend rate limit duration for abusive streams
            Cache::touch($rateLimitKey, 60);

            return response()->json(['error' => 'Rate limit exceeded.'], 429);
        }

        Cache::put($rateLimitKey, $attempts + 1, 60);

        return $next($request);
    }
}
