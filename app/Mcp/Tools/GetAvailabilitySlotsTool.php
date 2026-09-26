<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\GetAvailabilitySlotsTool as BackendGetAvailabilitySlotsTool;
use App\Mcp\Tools\Concerns\ResolvesTenant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_availability_slots')]
#[Title('Get Availability Slots')]
#[Description('Retrieve open technician availability slots for a specific date or skill.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class GetAvailabilitySlotsTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('Target date (YYYY-MM-DD or relative like "tomorrow", "next Monday").')
                ->required(),
            'service_type' => $schema->string()
                ->description('Optional service type or skill required.'),
            'tenant_id' => $schema->string()
                ->description('Optional tenant ID or slug.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return Response::error('Tenant context not found. Please provide tenant_id.');
        }

        $validated = $request->validate([
            'date' => ['required', 'string'],
            'service_type' => ['nullable', 'string'],
        ]);

        $backend = new BackendGetAvailabilitySlotsTool;
        $result = $backend->handle(
            (string) $tenant->id,
            (string) $validated['date'],
            isset($validated['service_type']) ? (string) $validated['service_type'] : null
        );

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to get availability slots.');
        }

        return Response::make(Response::text($result['message'] ?? 'Availability slots retrieved.'))
            ->withStructuredContent($result);
    }
}
