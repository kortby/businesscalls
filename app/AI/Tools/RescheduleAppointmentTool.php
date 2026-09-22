<?php

namespace App\AI\Tools;

use App\Events\DispatchUpdated;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class RescheduleAppointmentTool
{
    public function description(): string
    {
        return 'Reschedule an existing customer booking to a new start date/time.';
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
                'description' => 'The ID of the booking to reschedule.',
            ],
            'new_start_time' => [
                'type' => 'string',
                'description' => 'The new target start date and time.',
            ],
        ];
    }

    public function handle(string $tenant_id, int $booking_id, string $new_start_time): array
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

        try {
            $requestedTimeCarbon = Carbon::parse($new_start_time);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Invalid date format for new_start_time.',
            ];
        }

        $employee = $booking->employee;
        if (! $employee) {
            return [
                'status' => 'error',
                'message' => 'No technician assigned to this booking.',
            ];
        }

        $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
        $timeOnly = $requestedTimeCarbon->format('H:i:s');

        // Check shift availability
        $isAvailable = Availability::where('employee_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $timeOnly)
            ->where('end_time', '>=', $timeOnly)
            ->exists();

        if (! $isAvailable) {
            return [
                'status' => 'error',
                'message' => "Technician {$employee->first_name} is not scheduled to work during {$requestedTimeCarbon->format('l g:i A')}.",
            ];
        }

        // Check 90-min travel buffer
        $startBuffer = $requestedTimeCarbon->copy()->subMinutes(90);
        $endBuffer = $requestedTimeCarbon->copy()->addMinutes(90);

        $hasOverlap = Booking::where('employee_id', $employee->id)
            ->where('status', 'booked')
            ->where('id', '!=', $booking->id)
            ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
            ->exists();

        if ($hasOverlap) {
            return [
                'status' => 'error',
                'message' => 'Rescheduling conflict: Another appointment is scheduled within 1.5 hours of requested time.',
            ];
        }

        $booking->update([
            'scheduled_start' => $requestedTimeCarbon,
        ]);

        event(new DispatchUpdated($tenant->id, [
            'type' => 'success',
            'message' => "Booking #{$booking->id} rescheduled to {$requestedTimeCarbon->format('Y-m-d H:i')}.",
            'booking' => $booking->load('employee'),
        ]));

        return [
            'status' => 'success',
            'booking_id' => $booking->id,
            'technician_name' => "{$employee->first_name} {$employee->last_name}",
            'new_scheduled_start' => $requestedTimeCarbon->toIso8601String(),
            'message' => "Booking #{$booking->id} successfully rescheduled to {$requestedTimeCarbon->format('l, M j \a\t g:i A')}.",
        ];
    }
}
