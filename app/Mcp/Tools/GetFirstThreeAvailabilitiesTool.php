<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\GetFirstThreeAvailabilitiesTool as BackendGetFirstThreeAvailabilitiesTool;
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

#[Name('get_first_three_availabilities')]
#[Title('Get First Three Availabilities')]
#[Description('Get the first 3 available technician appointment slots formatted for customer presentation.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class GetFirstThreeAvailabilitiesTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'service_type' => $schema->string()
                ->description('Optional service type or skill required (e.g. plumbing, HVAC, electrical).'),
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

        $serviceType = $request->get('service_type');
        $backend = new BackendGetFirstThreeAvailabilitiesTool;
        $result = $backend->handle((string) $tenant->id, $serviceType ? (string) $serviceType : null);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to get first three availabilities.');
        }

        return Response::make(Response::text($result['message'] ?? 'First three availabilities retrieved.'))
            ->withStructuredContent($result);
    }
}
