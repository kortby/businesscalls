<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Database\Factories\KnowledgeBaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('knowledge_bases')]
#[Fillable('tenant_id', 'name', 'description')]
class KnowledgeBase extends Model
{
    /** @use HasFactory<KnowledgeBaseFactory> */
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the knowledge base.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the chunks for the knowledge base.
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
