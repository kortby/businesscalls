<?php

namespace App\AI\Tools;

use App\Models\Booking;
use App\Models\Tenant;

class LookupBookingTool
{
    public function description(): string
    {
        return 'Look up existing customer appointments by phone number or booking ID.';
    }

    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'customer_phone' => [
                'type' => 'string',
                'description' => 'Optional phone number to search bookings.',
            ],
            'booking_id' => [
                'type' => 'integer',
                'description' => 'Optional booking ID to look up.',
            ],
        ];
    }

    public function handle(string $tenant_id, ?string $customer_phone = null, ?int $booking_id = null): array
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

        $query = Booking::where('tenant_id', $tenant->id)->with('employee');

        if ($booking_id) {
            $query->where('id', $booking_id);
        } elseif ($customer_phone) {
            $query->where('customer_phone', 'like', "%{$customer_phone}%");
        } else {
            return [
                'status' => 'error',
                'message' => 'Please provide customer_phone or booking_id.',
            ];
        }

        $bookings = $query->latest()->take(5)->get();

        if ($bookings->isEmpty()) {
            return [
                'status' => 'success',
                'count' => 0,
                'bookings' => [],
                'message' => 'No active bookings found for the provided details.',
            ];
        }

        $formatted = $bookings->map(function ($b) {
            $employeeName = $b->employee ? "{$b->employee->first_name} {$b->employee->last_name}" : 'Unassigned';

            return [
                'booking_id' => $b->id,
                'customer_phone' => $b->customer_phone,
                'job_details' => $b->job_details,
                'status' => $b->status,
                'technician_name' => $employeeName,
                'scheduled_start' => $b->scheduled_start ? $b->scheduled_start->format('l, M j \a\t g:i A') : 'Unscheduled',
            ];
        });

        return [
            'status' => 'success',
            'count' => $formatted->count(),
            'bookings' => $formatted->toArray(),
            'message' => "Found {$formatted->count()} booking(s).",
        ];
    }
}
