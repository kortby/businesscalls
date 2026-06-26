<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('maintenance_agreements')]
#[Fillable('tenant_id', 'customer_id', 'system_type', 'last_service_date', 'status', 'next_service_due')]
#[Casts(['last_service_date' => 'date', 'next_service_due' => 'date'])]
class MaintenanceAgreement extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the maintenance agreement.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer associated with the maintenance agreement.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
