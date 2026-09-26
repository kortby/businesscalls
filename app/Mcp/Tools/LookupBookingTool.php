<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\LookupBookingTool as BackendLookupBookingTool;
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

#[Name('lookup_booking')]
#[Title('Lookup Customer Bookings')]
#[Description('Look up existing customer appointments by phone number or booking ID.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class LookupBookingTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_phone' => $schema->string()
                ->description('Optional customer phone number to look up.'),
            'booking_id' => $schema->integer()
                ->description('Optional booking ID to look up.'),
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

        $customerPhone = $request->get('customer_phone');
        $bookingId = $request->get('booking_id');

        $backend = new BackendLookupBookingTool;
        $result = $backend->handle(
            (string) $tenant->id,
            $customerPhone ? (string) $customerPhone : null,
            $bookingId !== null ? (int) $bookingId : null
        );

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to lookup booking.');
        }

        return Response::make(Response::text($result['message'] ?? 'Bookings retrieved.'))
            ->withStructuredContent($result);
    }
}
