<?php

namespace App\Observers;

use App\Jobs\SendOnMyWayAlertJob;
use App\Models\Booking;

class BookingObserver
{
    /**
     * Handle the Booking "saving" event.
     */
    public function saving(Booking $booking): void
    {
        // Replicate job_details into booking_notes if job_details was updated or set
        if ($booking->isDirty('job_details') && ! $booking->isDirty('booking_notes')) {
            $booking->booking_notes = $booking->job_details;
        }
        // Replicate booking_notes into job_details if booking_notes was updated or set
        elseif ($booking->isDirty('booking_notes') && ! $booking->isDirty('job_details')) {
            $booking->job_details = $booking->booking_notes;
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status') && $booking->status === 'en_route') {
            SendOnMyWayAlertJob::dispatch($booking);
        }
    }
}
