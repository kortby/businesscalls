<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class RouteOptimizerService
{
    /**
     * Calculate Haversine distance between two coordinates in kilometers.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Compute transit time in minutes assuming an average speed of 50 km/h.
     */
    public function getTransitTimeMinutes(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $distance = $this->calculateDistance($lat1, $lng1, $lat2, $lng2);

        // Drive time = (distance / 50 km/h) * 60 minutes
        $driveTime = ($distance / 50.0) * 60.0;

        return max(0.0, $driveTime);
    }

    /**
     * Compute the compatibility/density score for a technician based on the closest job on that day.
     * Score is 1.0 - (distance / 50.0), bounded to [0.0, 1.0].
     */
    public function calculateDensityScore(Employee $employee, Carbon $requestedTime, float $jobLat, float $jobLng): float
    {
        $date = $requestedTime->toDateString();

        // Get all active bookings for this employee today
        $bookings = Booking::where('employee_id', $employee->id)
            ->where('tenant_id', $employee->tenant_id)
            ->where('status', 'booked')
            ->whereDate('scheduled_start', $date)
            ->get();

        $minDistance = null;
        $refLat = $employee->latitude ?? 37.7749;
        $refLng = $employee->longitude ?? -122.4194;

        if ($bookings->count() > 0) {
            foreach ($bookings as $booking) {
                $bLat = $booking->latitude ?? 37.7749;
                $bLng = $booking->longitude ?? -122.4194;

                $dist = $this->calculateDistance($bLat, $bLng, $jobLat, $jobLng);

                if ($minDistance === null || $dist < $minDistance) {
                    $minDistance = $dist;
                    $refLat = $bLat;
                    $refLng = $bLng;
                }
            }
        } else {
            $minDistance = $this->calculateDistance($refLat, $refLng, $jobLat, $jobLng);
        }

        // Compatibility score: closer is better
        $score = 1.0 - ($minDistance / 50.0);

        return max(0.0, min(1.0, $score));
    }
}
