<?php

namespace App\AI\Tools;

use App\Events\DispatchUpdated;
use App\Models\Booking;
use App\Models\Tenant;

class CancelBookingTool
{
    public function description(): string
    {
        return 'Cancel an existing customer appointment booking.';
    }

    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'booking_id' => [
                'type' => 'integer',
                'description' => 'The ID of the booking to cancel.',
            ],
            'reason' => [
                'type' => 'string',
                'description' => 'Optional reason for cancellation.',
            ],
        ];
    }

    public function handle(string $tenant_id, int $booking_id, ?string $reason = null): array
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

        $booking = Booking::where('id', $booking_id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $booking) {
            return [
                'status' => 'error',
                'message' => "Booking #{$booking_id} not found.",
            ];
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        $employee = $booking->employee;
        $employeeName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'technician';

        event(new DispatchUpdated($tenant->id, [
            'type' => 'error',
            'message' => "Booking #{$booking->id} for {$booking->customer_phone} assigned to {$employeeName} has been cancelled.",
        ]));

        return [
            'status' => 'success',
            'booking_id' => $booking->id,
            'message' => "Booking #{$booking->id} has been successfully cancelled.",
        ];
    }
}
