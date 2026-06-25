<?php

namespace App\Services;

use App\Models\SemanticCache;
use App\Models\Tenant;
use Illuminate\Support\Str;

class SemanticCacheService
{
    /**
     * Search the cache for a semantically similar query and return the response if found.
     */
    public function get(Tenant $tenant, string $queryText): ?array
    {
        $queryEmbedding = Str::toEmbeddings($queryText);
        $caches = SemanticCache::where('tenant_id', $tenant->id)->get();

        foreach ($caches as $cache) {
            $similarity = $this->cosineSimilarity($queryEmbedding, $cache->vector_embedding);

            if ($similarity > 0.96) {
                return $cache->response_json;
            }
        }

        return null;
    }

    /**
     * Store a query and response in the semantic cache.
     */
    public function put(Tenant $tenant, string $queryText, array $response): void
    {
        $queryEmbedding = Str::toEmbeddings($queryText);

        SemanticCache::create([
            'tenant_id' => $tenant->id,
            'query_text' => $queryText,
            'vector_embedding' => $queryEmbedding,
            'response_json' => $response,
        ]);
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
