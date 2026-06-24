<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Database\Factories\KnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('knowledge_chunks')]
#[Fillable('knowledge_base_id', 'chunk_content', 'vector_embedding')]
#[Casts(['vector_embedding' => 'array'])]
class KnowledgeChunk extends Model
{
    /** @use HasFactory<KnowledgeChunkFactory> */
    use HasAttributeCasts, HasFactory;

    /**
     * Get the knowledge base that owns the chunk.
     */
    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}
