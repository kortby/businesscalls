<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('semantic_caches')]
#[Fillable('tenant_id', 'query_text', 'vector_embedding', 'response_json')]
#[Casts(['vector_embedding' => 'array', 'response_json' => 'array'])]
class SemanticCache extends Model
{
    use HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the cache.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
