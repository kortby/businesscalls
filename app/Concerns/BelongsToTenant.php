<?php

namespace App\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    /**
     * Boot the trait to apply TenantScope and set the tenant_id on creation.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->tenant_id && $tenantId = TenantScope::getTenantId()) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
