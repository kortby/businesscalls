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

#[Table('crm_credentials')]
#[Fillable('tenant_id', 'platform_name', 'access_token', 'refresh_token', 'token_expires_at', 'settings_json')]
#[Casts(['settings_json' => 'array', 'token_expires_at' => 'datetime'])]
class CrmCredential extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the master database connection name.
     */
    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get the tenant that owns the credential.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
