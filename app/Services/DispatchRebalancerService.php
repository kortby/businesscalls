<?php

namespace App\Services;

use App\Events\DispatchUpdated;
use App\Jobs\SendEtaUpdateSmsJob;
use App\Models\Booking;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class DispatchRebalancerService
{
    /**
     * Rebalance technician bookings schedule by inserting an emergency booking
     * at the front and shifting subsequent non-urgent appointments back.
     */
    public function rebalance(Booking $emergencyBooking, Employee $employee): void
    {
        $tenantId = $emergencyBooking->tenant_id;

        // Get the date of the emergency booking
        $date = Carbon::parse($emergencyBooking->scheduled_start);

        // Find all other booked (not completed/cancelled) appointments for the employee today
        // starting on or after the emergency booking start time
        $subsequentBookings = Booking::where('employee_id', $employee->id)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $emergencyBooking->id)
            ->where('status', 'booked')
            ->whereDate('scheduled_start', $date->toDateString())
            ->where('scheduled_start', '>=', $date)
            ->orderBy('scheduled_start', 'asc')
            ->get();

        // The emergency booking is placed at the front.
        // Subsequent bookings are pushed back by 120 minutes (2 hours).
        $pushBackMinutes = 120;
        $currentTime = $date->copy()->addMinutes($pushBackMinutes);

        foreach ($subsequentBookings as $booking) {
            $newStart = $currentTime->copy();

            $booking->update([
                'scheduled_start' => $newStart,
            ]);

            // Advance current time for the next appointment
            $currentTime->addMinutes(120);

            // Queue background SMS alert with updated ETA and "On My Way" link
            SendEtaUpdateSmsJob::dispatch($booking, $newStart);
        }

        // Broadcast the rebalanced board changes via Laravel Reverb
        event(new DispatchUpdated($tenantId, [
            'type' => 'route_rebalanced',
            'message' => "Emergency inserted! Route rebalanced for technician {$employee->first_name} {$employee->last_name}.",
            'employee_id' => $employee->id,
            'emergency_booking_id' => $emergencyBooking->id,
            'booking' => $emergencyBooking->load('employee'),
        ]));
    }
}
