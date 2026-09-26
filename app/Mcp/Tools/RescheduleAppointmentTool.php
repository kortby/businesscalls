<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\RescheduleAppointmentTool as BackendRescheduleAppointmentTool;
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

#[Name('reschedule_appointment')]
#[Title('Reschedule Appointment')]
#[Description('Reschedule an existing booking to a new start time, enforcing technician shifts and travel buffers.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
class RescheduleAppointmentTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'booking_id' => $schema->integer()
                ->description('The ID of the booking to reschedule.')
                ->required(),
            'new_start_time' => $schema->string()
                ->description('The new scheduled start time as an ISO-8601 date string.')
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
            'new_start_time' => ['required', 'string'],
        ]);

        $backend = new BackendRescheduleAppointmentTool;
        $result = $backend->handle((string) $tenant->id, (int) $validated['booking_id'], (string) $validated['new_start_time']);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to reschedule appointment.');
        }

        return Response::make(Response::text($result['message'] ?? 'Appointment rescheduled successfully.'))
            ->withStructuredContent($result);
    }
}
