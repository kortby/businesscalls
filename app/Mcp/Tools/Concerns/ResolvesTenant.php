<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Laravel\Mcp\Request;

trait ResolvesTenant
{
    /**
     * Resolve the target tenant model from request arguments, scope, auth user, or fallback.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        $tenantId = $request->get('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::where('id', $tenantId)
                ->orWhere('slug', $tenantId)
                ->first();

            if ($tenant) {
                TenantScope::setTenantId($tenant->id);

                return $tenant;
            }
        }

        $activeId = TenantScope::getTenantId();
        if ($activeId) {
            return Tenant::find($activeId);
        }

        $user = $request->user();
        if ($user && isset($user->tenant_id)) {
            TenantScope::setTenantId((int) $user->tenant_id);

            return Tenant::find($user->tenant_id);
        }

        $firstTenant = Tenant::first();
        if ($firstTenant) {
            TenantScope::setTenantId($firstTenant->id);

            return $firstTenant;
        }

        return null;
    }
}
