<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * The active tenant ID.
     */
    protected static ?int $tenantId = null;

    /**
     * Flag to prevent recursion during auth resolution.
     */
    protected static bool $resolving = false;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = static::getTenantId()) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    /**
     * Set the active tenant ID for the current request lifecycle.
     */
    public static function setTenantId(?int $tenantId): void
    {
        static::$tenantId = $tenantId;
        if ($tenantId !== null && request()->hasSession()) {
            session(['tenant_id' => $tenantId]);
        }
    }

    /**
     * Get the active tenant ID from static memory, session, or authenticated user.
     */
    public static function getTenantId(): ?int
    {
        if (static::$tenantId !== null) {
            return static::$tenantId;
        }

        if (request()->hasSession() && session()->has('tenant_id')) {
            return session('tenant_id');
        }

        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;

        try {
            if (Auth::hasUser()) {
                $user = Auth::user();

                return $user->tenant_id ?? null;
            }

            if (Auth::check()) {
                $user = Auth::user();
                $id = $user->tenant_id ?? null;
                if ($id !== null && request()->hasSession()) {
                    session(['tenant_id' => $id]);
                }

                return $id;
            }
        } finally {
            static::$resolving = false;
        }

        return null;
    }
}
