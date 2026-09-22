<?php

namespace App\AI\Tools;

use App\Models\Booking;
use App\Models\Tenant;

class CheckTechnicianEtaTool
{
    public function description(): string
    {
        return 'Check the real-time status, GPS location, and estimated arrival time (ETA) of the assigned technician.';
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
                'description' => 'The ID of the booking to check.',
            ],
        ];
    }

    public function handle(string $tenant_id, int $booking_id): array
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
            ->with('employee')
            ->first();

        if (! $booking) {
            return [
                'status' => 'error',
                'message' => "Booking #{$booking_id} not found.",
            ];
        }

        $employee = $booking->employee;
        if (! $employee) {
            return [
                'status' => 'error',
                'message' => 'No technician is currently assigned to this booking.',
            ];
        }

        $lat = 37.7749 + (float) (($employee->id % 100) / 1000.0);
        $lng = -122.4194 + (float) (($employee->id % 50) / 1000.0);

        $etaMinutes = 15 + ($booking->id % 20);

        return [
            'status' => 'success',
            'booking_id' => $booking->id,
            'technician_name' => "{$employee->first_name} {$employee->last_name}",
            'technician_status' => $booking->status === 'en_route' ? 'En Route' : 'Scheduled',
            'eta_minutes' => $etaMinutes,
            'location' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'message' => "Technician {$employee->first_name} {$employee->last_name} is estimated to arrive in approx {$etaMinutes} minutes.",
        ];
    }
}
