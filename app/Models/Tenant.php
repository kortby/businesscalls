<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

#[Table('tenants')]
#[Fillable('slug', 'name', 'plan', 'settings', 'secret_key', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at', 'is_test_mode')]
#[Casts(['settings' => 'array', 'is_test_mode' => 'boolean'])]
class Tenant extends Model
{
    use Billable, HasAttributeCasts, HasFactory;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get a setting by key.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Get the employees associated with the tenant.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the bookings associated with the tenant.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the users associated with the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the database shard associated with the tenant.
     */
    public function tenantShard(): HasOne
    {
        return $this->hasOne(TenantShard::class);
    }
}
