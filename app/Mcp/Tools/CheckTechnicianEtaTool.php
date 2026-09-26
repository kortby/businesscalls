<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\CheckTechnicianEtaTool as BackendCheckTechnicianEtaTool;
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

#[Name('check_technician_eta')]
#[Title('Check Technician ETA')]
#[Description('Check real-time status, GPS location, and estimated arrival time (ETA) of assigned technician.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class CheckTechnicianEtaTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'booking_id' => $schema->integer()
                ->description('The ID of the booking.')
                ->required(),
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
            'booking_id' => ['required', 'integer'],
        ]);

        $backend = new BackendCheckTechnicianEtaTool;
        $result = $backend->handle((string) $tenant->id, (int) $validated['booking_id']);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to check technician ETA.');
        }

        return Response::make(Response::text($result['message'] ?? 'Technician ETA retrieved.'))
            ->withStructuredContent($result);
    }
}
