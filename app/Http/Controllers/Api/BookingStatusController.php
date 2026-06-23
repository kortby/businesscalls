<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingStatusController extends Controller
{
    /**
     * Update the dispatch booking status dynamically.
     */
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Apply tenant isolation scope
        TenantScope::setTenantId($booking->tenant_id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:en_route,on_site,completed'],
            'feedback' => ['nullable', 'string', 'max:500'],
            'billing_amount' => ['nullable', 'numeric'],
        ]);

        $status = $validated['status'];
        Log::info("Technician updated booking {$booking->id} status to: {$status}");

        if ($status === 'en_route') {
            $booking->status = 'en_route';
            $booking->en_route_at = now();
            $booking->save();
        } elseif ($status === 'on_site') {
            $booking->status = 'on_site';
            $booking->on_site_at = now();

            // Calculate transit duration in hours
            if ($booking->en_route_at) {
                $diffInMinutes = $booking->en_route_at->diffInMinutes(now());
                // Store transit duration in decimal hours (e.g. 30 mins = 0.5 hours)
                $booking->travel_time = round($diffInMinutes / 60, 4);
            } else {
                // Default fallback if en_route check-in was skipped
                $booking->travel_time = 0.5;
            }
            $booking->save();
        } elseif ($status === 'completed') {
            $booking->status = 'completed';
            $booking->completed_at = now();

            // Commit feedback notes/billing parameters if provided
            if ($validated['feedback']) {
                $booking->job_details .= "\n\nTechnician Feedback: ".$validated['feedback'];
            }
            if ($validated['billing_amount']) {
                $booking->job_details .= "\nBilling Amount: $".number_format($validated['billing_amount'], 2);
            }
            $booking->save();
        }

        // Broadcast Reverb updates to update admin dashboard instantly
        event(new BookingStatusUpdated($booking->tenant_id, $booking->load('employee')));

        return response()->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }
}
