<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\DispatchTechnicianTool as BackendDispatchTechnicianTool;
use App\Mcp\Tools\Concerns\ResolvesTenant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('dispatch_technician')]
#[Title('Dispatch Technician')]
#[Description('Dispatch optimal technician to a booking based on workload metrics and required skills.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
class DispatchTechnicianTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'booking_id' => $schema->integer()
                ->description('The ID of the booking to dispatch.')
                ->required(),
            'required_skill' => $schema->string()
                ->description('Optional required technician skill (e.g. plumbing, HVAC).'),
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
            'required_skill' => ['nullable', 'string'],
        ]);

        $backend = new BackendDispatchTechnicianTool;
        $result = $backend->handle(
            (string) $tenant->id,
            (int) $validated['booking_id'],
            isset($validated['required_skill']) ? (string) $validated['required_skill'] : null
        );

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to dispatch technician.');
        }

        return Response::make(Response::text($result['message'] ?? 'Technician dispatched.'))
            ->withStructuredContent($result);
    }
}
