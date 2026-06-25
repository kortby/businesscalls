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
#[Fillable('slug', 'name', 'plan', 'settings', 'secret_key', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at', 'is_test_mode', 'client_id', 'client_secret', 'domain')]
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

    /**
     * Get the call logs associated with the tenant.
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the payment transactions associated with the tenant.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the webhooks associated with the tenant.
     */
    public function tenantWebhooks(): HasMany
    {
        return $this->hasMany(TenantWebhook::class);
    }

    /**
     * Calculate spend usage for the current billing cycle.
     */
    public function calculateSpendUsage(): float
    {
        $startDate = now()->startOfMonth();
        $calls = $this->callLogs()
            ->where('created_at', '>=', $startDate)
            ->get();

        $spend = 0.0;
        $blendedRate = (float) $this->getSetting('blended_rate', 0.15);
        $integrationSurcharge = (float) $this->getSetting('integration_surcharge', 0.05);
        $ragSurcharge = (float) $this->getSetting('rag_surcharge', 0.01);

        foreach ($calls as $call) {
            $durationInMinutes = ($call->duration ?? 0) / 60.0;
            $spend += ($durationInMinutes * $blendedRate);
            $spend += ($call->integrations_count * $integrationSurcharge);
            $spend += ($call->rag_lookups_count * $ragSurcharge);
        }

        return $spend;
    }

    /**
     * Get the active spend limit.
     */
    public function getSpendLimit(): float
    {
        return (float) $this->getSetting('spend_limit', 50.0);
    }
}
