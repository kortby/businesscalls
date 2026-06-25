<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantOAuthToken;
use App\Models\TenantShard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyOAuthWebhookToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $signature = $request->header('x-vapi-signature') ?? $request->header('x-signature') ?? $request->input('signature');
        $vapiSecret = $request->header('X-Vapi-Secret') ?? $request->header('x-vapi-secret');
        $retellSecret = $request->header('X-Retell-Secret') ?? $request->header('x-retell-secret');

        // Resolve Tenant first
        $tenantId = $request->input('tenant_id')
            ?? $request->input('tenant_slug')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_id')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_slug');

        $tenant = null;
        if ($tenantId) {
            $tenant = Tenant::where('id', $tenantId)->orWhere('slug', $tenantId)->first();
        }

        // If the tenant does not have a secret key or client credentials configured,
        // bypass verification check (backward compatibility / unsecured mode)
        if ($tenant && empty($tenant->secret_key) && empty($tenant->client_id)) {
            if ($tenant->id) {
                self::swapConnection($tenant->id);
            }

            return $next($request);
        }

        if ($token) {
            // Check if it is the static secret key (legacy compatibility)
            if ($tenant && $tenant->secret_key && hash_equals($tenant->secret_key, $token)) {
                if ($tenant->id) {
                    self::swapConnection($tenant->id);
                }

                return $next($request);
            }

            $tokenRecord = TenantOAuthToken::where('access_token', $token)->first();

            if (! $tokenRecord) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid bearer token.',
                ], 401);
            }

            if ($tokenRecord->expires_at->isPast()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Expired bearer token.',
                ], 401);
            }

            self::swapConnection($tokenRecord->tenant_id);

            return $next($request);
        }

        // If no bearer token is present, but a signature or Vapi/Retell secret is,
        // we defer security check to WebhookGatewayMiddleware.
        if ($signature || $vapiSecret || $retellSecret) {
            if ($tenant && $tenant->id) {
                self::swapConnection($tenant->id);
            }

            return $next($request);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Bearer token is missing.',
        ], 401);
    }

    /**
     * Swap dynamic connection configuration to the tenant's database shard.
     */
    private static function swapConnection(int|string $tenantId): void
    {
        $shard = TenantShard::where('tenant_id', $tenantId)->first();

        if ($shard) {
            $shardConfig = $shard->database_config;

            if (empty($shardConfig)) {
                $shardConfig = [
                    'driver' => $shard->driver ?? 'sqlite',
                    'host' => $shard->host,
                    'port' => $shard->port,
                    'database' => $shard->database,
                    'username' => $shard->username,
                    'password' => $shard->password,
                    'prefix' => '',
                ];

                if ($shardConfig['driver'] === 'sqlite') {
                    $shardConfig['foreign_key_constraints'] = true;
                }
            }

            DB::purge('tenant');
            config()->set('database.connections.tenant', $shardConfig);
            DB::setDefaultConnection('tenant');
        }
    }
}
