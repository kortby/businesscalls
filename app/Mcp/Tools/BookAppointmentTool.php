<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\BookAppointmentTool as BackendBookAppointmentTool;
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

#[Name('book_appointment')]
#[Title('Book Customer Appointment')]
#[Description('Book an appointment for a customer with an available technician or time slot.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
class BookAppointmentTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_phone' => $schema->string()
                ->description('Customer phone number (e.g. +15551234567).')
                ->required(),
            'job_details' => $schema->string()
                ->description('Service job description (e.g. AC maintenance, leaky pipe repair).')
                ->required(),
            'scheduled_start' => $schema->string()
                ->description('Target scheduled start datetime (ISO-8601 or Y-m-d H:i:s).')
                ->required(),
            'employee_id' => $schema->integer()
                ->description('Optional technician ID to assign specifically.'),
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
            'customer_phone' => ['required', 'string'],
            'job_details' => ['required', 'string'],
            'scheduled_start' => ['required', 'string'],
            'employee_id' => ['nullable', 'integer'],
        ]);

        $backend = new BackendBookAppointmentTool;
        $result = $backend->handle(
            (string) $tenant->id,
            (string) $validated['customer_phone'],
            (string) $validated['job_details'],
            (string) $validated['scheduled_start'],
            isset($validated['employee_id']) ? (int) $validated['employee_id'] : null
        );

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to book appointment.');
        }

        return Response::make(Response::text($result['message'] ?? 'Appointment booked successfully.'))
            ->withStructuredContent($result);
    }
}
