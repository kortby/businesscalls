<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Database\Factories\CustomVoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('custom_voices')]
#[Fillable('tenant_id', 'voice_name', 'provider_voice_id', 'status')]
class CustomVoice extends Model
{
    /** @use HasFactory<CustomVoiceFactory> */
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the custom voice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
