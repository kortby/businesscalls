<?php

namespace App\AI\Tools;

use App\Models\Tenant;

class CheckInventoryTool
{
    public function description(): string
    {
        return 'Check if a specific part or item is in stock in the inventory.';
    }

    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'part_name' => [
                'type' => 'string',
                'description' => 'The name of the part to search (e.g. faucet, pipe, wire, thermostat).',
            ],
        ];
    }

    public function handle(string $tenant_id, string $part_name): array
    {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant '{$tenant_id}' not found.",
            ];
        }

        $partKey = strtolower(trim($part_name));
        $inventory = $tenant->getSetting('inventory', [
            'faucet' => 15,
            'pipe' => 24,
            'wire' => 50,
            'thermostat' => 8,
            'filter' => 12,
            'capacitor' => 6,
        ]);

        $inventory = array_change_key_case($inventory, CASE_LOWER);
        $qty = $inventory[$partKey] ?? 0;

        return [
            'status' => 'success',
            'part_name' => $partKey,
            'in_stock' => $qty > 0,
            'quantity' => $qty,
            'message' => $qty > 0
                ? "Part '{$partKey}' is in stock (Quantity: {$qty})."
                : "Part '{$partKey}' is currently out of stock.",
        ];
    }
}
