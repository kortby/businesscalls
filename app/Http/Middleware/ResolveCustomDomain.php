<?php

namespace App\Http\Middleware;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantShard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveCustomDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Query the master database for a tenant with this custom domain
        $tenant = Tenant::where('domain', $host)->first();

        if ($tenant) {
            TenantScope::setTenantId($tenant->id);

            // Configure connection dynamically to tenant shard
            $shard = TenantShard::where('tenant_id', $tenant->id)->first();
            if ($shard) {
                $shardConfig = $shard->database_config;
                if (empty($shardConfig)) {
                    $shardConfig = [
                        'driver' => $shard->driver ?? 'sqlite',
                        'database' => $shard->database,
                        'prefix' => '',
                    ];
                    if ($shardConfig['driver'] === 'sqlite') {
                        $shardConfig['foreign_key_constraints'] = true;
                    } else {
                        $shardConfig['host'] = $shard->host;
                        $shardConfig['port'] = $shard->port;
                        $shardConfig['username'] = $shard->username;
                        $shardConfig['password'] = $shard->password;
                    }
                }
                DB::purge('tenant');
                config()->set('database.connections.tenant', $shardConfig);
                DB::setDefaultConnection('tenant');
            }
        }

        return $next($request);
    }
}
