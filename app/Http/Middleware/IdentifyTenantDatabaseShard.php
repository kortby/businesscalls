<?php

namespace App\Http\Middleware;

use App\Models\TenantOAuthToken;
use App\Models\TenantShard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantDatabaseShard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id
            ?? $request->input('tenant_id')
            ?? $request->route('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? $request->header('x-tenant-id');

        if (! $tenantId && $request->bearerToken()) {
            $tokenRecord = TenantOAuthToken::where('access_token', $request->bearerToken())->first();
            if ($tokenRecord) {
                $tenantId = $tokenRecord->tenant_id;
            }
        }

        if ($tenantId) {
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

                // Purge connection config and swap runtime connection details
                DB::purge('tenant');
                config()->set('database.connections.tenant', $shardConfig);
                DB::setDefaultConnection('tenant');
            }
        }

        return $next($request);
    }
}
