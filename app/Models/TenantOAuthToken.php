<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tenant_oauth_tokens')]
#[Fillable('tenant_id', 'access_token', 'expires_at')]
#[Casts(['expires_at' => 'datetime'])]
class TenantOAuthToken extends Model
{
    use HasAttributeCasts, HasFactory;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get the tenant that owns the token.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
