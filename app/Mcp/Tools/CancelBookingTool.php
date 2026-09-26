<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\CancelBookingTool as BackendCancelBookingTool;
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

#[Name('cancel_booking')]
#[Title('Cancel Booking')]
#[Description('Cancel an existing customer appointment booking.')]
#[IsReadOnly(false)]
#[IsDestructive(true)]
class CancelBookingTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'booking_id' => $schema->integer()
                ->description('The ID of the booking to cancel.')
                ->required(),
            'reason' => $schema->string()
                ->description('Optional reason for cancellation.'),
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
            'reason' => ['nullable', 'string'],
        ]);

        $backend = new BackendCancelBookingTool;
        $result = $backend->handle(
            (string) $tenant->id,
            (int) $validated['booking_id'],
            isset($validated['reason']) ? (string) $validated['reason'] : null
        );

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to cancel booking.');
        }

        return Response::make(Response::text($result['message'] ?? 'Booking cancelled.'))
            ->withStructuredContent($result);
    }
}
