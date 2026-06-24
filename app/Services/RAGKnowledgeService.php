<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\Tenant;
use Illuminate\Support\Str;

class RAGKnowledgeService
{
    /**
     * Chunk and ingest a raw manual text into the knowledge base.
     */
    public function ingest(KnowledgeBase $knowledgeBase, string $text, int $chunkSize = 500, int $chunkOverlap = 50): void
    {
        // Clear existing chunks for a clean ingest of this manual
        $knowledgeBase->chunks()->delete();

        if (empty(trim($text))) {
            return;
        }

        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunkText = mb_substr($text, $start, $chunkSize);
            $trimmed = trim($chunkText);

            if (! empty($trimmed)) {
                $embedding = Str::toEmbeddings($trimmed);

                KnowledgeChunk::create([
                    'knowledge_base_id' => $knowledgeBase->id,
                    'chunk_content' => $trimmed,
                    'vector_embedding' => $embedding,
                ]);
            }

            $start += ($chunkSize - $chunkOverlap);
            if ($start >= $length || $chunkSize <= $chunkOverlap) {
                break;
            }
        }
    }

    /**
     * Perform a semantic lookup using cosine similarity and rank decay.
     *
     * @return array<array{chunk: KnowledgeChunk, similarity: float, score: float, rank: int}>
     */
    public function search(Tenant $tenant, string $query, int $limit = 5, float $beta = 2.0): array
    {
        $knowledgeBaseIds = KnowledgeBase::where('tenant_id', $tenant->id)->pluck('id');

        if ($knowledgeBaseIds->isEmpty()) {
            return [];
        }

        $queryEmbedding = Str::toEmbeddings($query);
        $chunks = KnowledgeChunk::whereIn('knowledge_base_id', $knowledgeBaseIds)->get();

        $results = [];

        foreach ($chunks as $chunk) {
            $chunkEmbedding = $chunk->vector_embedding;
            $similarity = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding);

            $results[] = [
                'chunk' => $chunk,
                'similarity' => $similarity,
            ];
        }

        // Sort by baseline similarity descending
        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Compute rank-decayed score: Xi_RAG = similarity * (1 - ln(1 + rank) / (1 + beta))
        foreach ($results as $rank => &$result) {
            $result['rank'] = $rank;
            $decay = 1.0 - (log(1.0 + $rank) / (1.0 + $beta));
            // Ensure decay doesn't go negative or exceed 1
            $decay = max(0.0, min(1.0, $decay));
            $result['score'] = $result['similarity'] * $decay;
        }
        unset($result);

        // Re-sort by the rank-decayed score descending
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $magA = 0.0;
        $magB = 0.0;
        $count = max(count($vecA), count($vecB));

        for ($i = 0; $i < $count; $i++) {
            $a = $vecA[$i] ?? 0.0;
            $b = $vecB[$i] ?? 0.0;
            $dotProduct += $a * $b;
            $magA += $a * $a;
            $magB += $b * $b;
        }

        $denom = sqrt($magA) * sqrt($magB);

        return $denom > 0.0 ? ($dotProduct / $denom) : 0.0;
    }
}
