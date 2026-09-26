<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\CheckAvailabilityTool as BackendCheckAvailabilityTool;
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

#[Name('check_availability')]
#[Title('Check Technician Availability')]
#[Description('Check the first available technician appointment slots based on service type over the next 14 days.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class CheckAvailabilityTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'service_type' => $schema->string()
                ->description('Optional service type or required skill (e.g. plumbing, HVAC, electrical).'),
            'tenant_id' => $schema->string()
                ->description('Optional tenant ID or slug if multi-tenant context is required.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return Response::error('Tenant context not found. Please provide tenant_id.');
        }

        $serviceType = $request->get('service_type');
        $backend = new BackendCheckAvailabilityTool;
        $result = $backend->handle((string) $tenant->id, $serviceType ? (string) $serviceType : null);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to check availability.');
        }

        return Response::make(Response::text($result['message'] ?? 'Availability checked successfully.'))
            ->withStructuredContent($result);
    }
}
