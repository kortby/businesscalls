<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('call_logs')]
#[Fillable('tenant_id', 'call_id', 'status', 'customer_phone', 'transcript', 'recording_url', 'summary', 'duration', 'csat_score', 'call_end_reason', 'disconnection_source', 'latency', 'transcription_confidence', 'tool_success_rate', 'call_quality_score', 'is_test_mode')]
#[Casts(['is_test_mode' => 'boolean'])]
class CallLog extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the call log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Calculate and store the CSAT score using the formula.
     *
     * @param  array<int>  $ratings
     */
    public function calculateCsatScore(array $ratings): ?float
    {
        if (empty($ratings)) {
            return null;
        }

        $sum = array_sum($ratings);
        $K = count($ratings);

        $score = ($sum / (5 * $K)) * 100;

        $this->csat_score = $score;
        $this->save();

        return $score;
    }

    /**
     * Calculate and store the Call Quality Score (CQS).
     */
    public function calculateCqsScore(?int $latency, ?float $transcriptionConfidence, ?float $toolSuccessRate): ?float
    {
        $tenant = $this->tenant;
        $plan = strtolower($tenant?->plan ?? 'trial');

        $weights = config("telephony.cqs_weights.{$plan}") ?? config('telephony.cqs_weights.trial');

        $w1 = (float) ($weights['w1'] ?? 0.3);
        $w2 = (float) ($weights['w2'] ?? 0.3);
        $w3 = (float) ($weights['w3'] ?? 0.4);

        // Delta represents latency. Ensure delta term: (1 - Delta / 1500) is bounded [0, 1]
        $delta = $latency ?? 0;
        $latencyTerm = max(0.0, min(1.0, 1.0 - ($delta / 1500.0)));

        // Theta represents confidence
        $theta = $transcriptionConfidence ?? 1.0;
        $theta = max(0.0, min(1.0, $theta));

        // Epsilon represents tool success rate
        $epsilon = $toolSuccessRate ?? 1.0;
        $epsilon = max(0.0, min(1.0, $epsilon));

        $cqs = ($w1 * $latencyTerm) + ($w2 * $theta) + ($w3 * $epsilon);

        // Ensure CQS itself is bounded to [0.0, 1.0]
        $cqs = max(0.0, min(1.0, $cqs));

        $this->latency = $latency;
        $this->transcription_confidence = $transcriptionConfidence;
        $this->tool_success_rate = $toolSuccessRate;
        $this->call_quality_score = $cqs;
        $this->save();

        return $cqs;
    }
}
