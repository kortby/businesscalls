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
#[Fillable('tenant_id', 'call_id', 'status', 'customer_phone', 'transcript', 'recording_url', 'summary', 'duration', 'csat_score', 'call_end_reason', 'disconnection_source', 'latency', 'transcription_confidence', 'tool_success_rate', 'call_quality_score', 'is_test_mode', 'latency_drift', 'turn_taking_congruence', 'cost', 'experiment_variant_id', 'snr_raw', 'snr_processed', 'denoising_quality_improvement', 'mos_score', 'acoustic_intelligibility', 'vocal_inflection_variance', 'conversational_eval_score', 'performance_score', 'contextual_handover_match_index', 'user_sentiment', 'job_category', 'performance_scorecard')]
#[Casts(['is_test_mode' => 'boolean', 'latency_drift' => 'double', 'turn_taking_congruence' => 'double', 'cost' => 'double', 'experiment_variant_id' => 'integer', 'snr_raw' => 'double', 'snr_processed' => 'double', 'denoising_quality_improvement' => 'double', 'mos_score' => 'double', 'acoustic_intelligibility' => 'double', 'vocal_inflection_variance' => 'double', 'conversational_eval_score' => 'double', 'performance_score' => 'double', 'contextual_handover_match_index' => 'double', 'performance_scorecard' => 'array'])]
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

    /**
     * Calculate and store the Speech Turn-Taking Congruence Index.
     *
     * @param  array<int>  $actualPauses
     */
    public function calculateTurnTakingCongruence(array $actualPauses): ?float
    {
        if (empty($actualPauses)) {
            return null;
        }

        $c = count($actualPauses);
        $pTarget = 600.0;
        $sum = 0.0;

        foreach ($actualPauses as $pActual) {
            $sum += (1.0 - (abs($pActual - $pTarget) / $pTarget));
        }

        $congruence = $sum / $c;

        $this->turn_taking_congruence = $congruence;
        $this->save();

        return $congruence;
    }

    /**
     * Calculate and return the CRM Synchronization Efficiency Index (Psi_sync) for the tenant.
     */
    public function calculateCrmSyncEfficiency(): float
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            return 0.0;
        }

        $total = $tenant->callLogs()->count();
        if ($total === 0) {
            return 0.0;
        }

        $success = $tenant->callLogs()->where('crm_sync_status', 'success')->count();
        $avgLatency = $tenant->callLogs()->where('crm_sync_status', 'success')->avg('crm_sync_latency') ?? 0.0;

        $tMax = 5000.0;
        $latencyTerm = max(0.0, 1.0 - ($avgLatency / $tMax));

        return ($success / $total) * $latencyTerm;
    }
}
