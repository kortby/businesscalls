<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

class DiagnosticValidationService
{
    /**
     * Compute the Diagnostic Quality & Triaging Coefficient (Psi_triage) for a booking.
     */
    public function calculateTriageScore(Booking $booking): float
    {
        $hasNotes = ! empty($booking->triage_notes) ? 1.0 : 0.0;
        $hasBrand = ! empty($booking->appliance_brand) ? 1.0 : 0.0;
        $hasAge = ($booking->appliance_age !== null && $booking->appliance_age >= 0) ? 1.0 : 0.0;
        $hasMarkers = (! empty($booking->urgency_markers) && is_array($booking->urgency_markers) && count($booking->urgency_markers) > 0) ? 1.0 : 0.0;

        return ($hasNotes + $hasBrand + $hasAge + $hasMarkers) / 4.0;
    }

    /**
     * Calculate and log the score to AuditLog under isolated tenant context.
     */
    public function calculateAndSave(Booking $booking): float
    {
        $tenantId = $booking->tenant_id;
        TenantScope::setTenantId($tenantId);

        try {
            $score = $this->calculateTriageScore($booking);

            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'action' => 'triage_index_calculation',
                'ip_address' => request()->ip(),
                'browser_agent' => request()->userAgent(),
                'payload' => [
                    'booking_id' => $booking->id,
                    'psi_triage' => $score,
                    'details' => [
                        'notes_present' => ! empty($booking->triage_notes),
                        'brand_present' => ! empty($booking->appliance_brand),
                        'age_present' => $booking->appliance_age !== null && $booking->appliance_age >= 0,
                        'markers_present' => ! empty($booking->urgency_markers) && is_array($booking->urgency_markers) && count($booking->urgency_markers) > 0,
                    ],
                ],
            ]);

            Log::info("Diagnostic Quality & Triaging Coefficient computed for Booking {$booking->id}: {$score}");

            return $score;
        } finally {
            TenantScope::setTenantId(null);
        }
    }
}
