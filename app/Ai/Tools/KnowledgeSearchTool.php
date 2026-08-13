<?php

namespace App\AI\Tools;

use App\Models\Tenant;
use App\Services\RAGKnowledgeService;

class KnowledgeSearchTool
{
    /**
     * Get tool description for AI function schema.
     */
    public function description(): string
    {
        return 'Search the tenant knowledge base for diagnostic manuals, policies, and pricing guidelines.';
    }

    /**
     * Get tool parameters schema.
     */
    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'query' => [
                'type' => 'string',
                'description' => 'The search phrase or customer question.',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(string $tenant_id, string $query): array
    {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant with ID or slug '{$tenant_id}' not found.",
            ];
        }

        $ragService = new RAGKnowledgeService;
        $searchResults = $ragService->search($tenant, $query, 3);

        $snippets = [];
        foreach ($searchResults as $item) {
            $snippets[] = [
                'content' => $item['chunk']->chunk_content,
                'score' => round($item['score'], 3),
            ];
        }

        return [
            'status' => 'success',
            'query' => $query,
            'results_count' => count($snippets),
            'snippets' => $snippets,
            'message' => count($snippets) > 0
                ? 'Found '.count($snippets).' relevant knowledge snippets.'
                : 'No relevant knowledge base entries found.',
        ];
    }
}
