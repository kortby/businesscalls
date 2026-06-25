<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tenant_integrations')]
#[Fillable('tenant_id', 'platform_name', 'webhook_url', 'is_active', 'settings_json')]
#[Casts(['settings_json' => 'array', 'is_active' => 'boolean'])]
class TenantIntegration extends Model
{
    use HasAttributeCasts, HasFactory;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get the tenant that owns the integration.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
