<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\CheckInventoryTool as BackendCheckInventoryTool;
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

#[Name('check_inventory')]
#[Title('Check Parts Inventory')]
#[Description('Check if a part or item (e.g., faucet, pipe, wire, thermostat, filter) is in stock in tenant inventory.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
class CheckInventoryTool extends Tool
{
    use ResolvesTenant;

    public function schema(JsonSchema $schema): array
    {
        return [
            'part_name' => $schema->string()
                ->description('The name of the part to search (e.g. faucet, pipe, wire, thermostat).')
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
            'part_name' => ['required', 'string'],
        ]);

        $partName = strtolower(trim($validated['part_name']));

        // Check tenant settings inventory first
        $inventory = $tenant->getSetting('inventory', []);
        $inventory = is_array($inventory) ? array_change_key_case($inventory, CASE_LOWER) : [];

        if (array_key_exists($partName, $inventory)) {
            $qty = (int) $inventory[$partName];
            $inStock = $qty > 0;
            $text = $inStock
                ? "The part '{$partName}' is in stock. Current quantity: {$qty}."
                : "The part '{$partName}' is out of stock.";

            return Response::make(Response::text($text))
                ->withStructuredContent([
                    'status' => 'success',
                    'part_name' => $partName,
                    'in_stock' => $inStock,
                    'quantity' => $qty,
                    'message' => $text,
                ]);
        }

        $backend = new BackendCheckInventoryTool;
        $result = $backend->handle((string) $tenant->id, $partName);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to check inventory.');
        }

        $text = ($result['in_stock'] ?? false)
            ? "The part '{$partName}' is in stock. Current quantity: ".($result['quantity'] ?? 0).'.'
            : "The part '{$partName}' is out of stock.";

        return Response::make(Response::text($text))
            ->withStructuredContent(array_merge($result, ['message' => $text]));
    }
}
