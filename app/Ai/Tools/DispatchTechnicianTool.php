<?php

namespace App\AI\Tools;

use App\Models\Booking;
use App\Models\Tenant;
use App\Services\PredictiveAllocationService;

class DispatchTechnicianTool
{
    /**
     * Get tool description for AI function schema.
     */
    public function description(): string
    {
        return 'Dispatch the optimal technician to a booking based on workload metrics and required skill.';
    }

    /**
     * Get tool parameters schema.
     */
    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'booking_id' => [
                'type' => 'integer',
                'description' => 'The ID of the booking to dispatch.',
            ],
            'required_skill' => [
                'type' => 'string',
                'description' => 'Optional skill required (e.g. plumbing, HVAC, electrical).',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(string $tenant_id, int $booking_id, ?string $required_skill = null): array
    {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant with ID or slug '{$tenant_id}' not found.",
            ];
        }

        $booking = Booking::where('id', $booking_id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $booking) {
            return [
                'status' => 'error',
                'message' => "Booking #{$booking_id} not found.",
            ];
        }

        $allocationService = new PredictiveAllocationService;
        $technician = $allocationService->allocateTechnician($tenant, $booking, $required_skill);

        if (! $technician) {
            return [
                'status' => 'error',
                'message' => 'No available technician found matching dispatch rules.',
            ];
        }

        return [
            'status' => 'success',
            'booking_id' => $booking->id,
            'technician_id' => $technician->id,
            'technician_name' => "{$technician->first_name} {$technician->last_name}",
            'message' => "Technician {$technician->first_name} {$technician->last_name} successfully dispatched to Booking #{$booking->id}.",
        ];
    }
}
