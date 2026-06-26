<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Scopes\TenantScope;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TrackingController extends Controller
{
    /**
     * Display the public guest tracking map view.
     */
    public function show(string $hash): InertiaResponse
    {
        // Temporarily clear tenant scope to find the booking across any tenant
        TenantScope::setTenantId(null);

        $booking = Booking::where('booking_hash', $hash)->firstOrFail();

        // Restore tenant isolation scope context
        TenantScope::setTenantId($booking->tenant_id);

        $booking->load(['employee', 'tenant']);

        return Inertia::render('BookingTracking', [
            'booking' => $booking,
            'reverbKey' => env('REVERB_APP_KEY'),
            'reverbHost' => env('REVERB_HOST'),
            'reverbPort' => env('REVERB_PORT'),
            'reverbScheme' => env('REVERB_SCHEME'),
        ]);
    }
}
