<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

class VoicePerformanceService
{
    /**
     * Compute the overall Speech Latency and Clarity Coefficient (Omega_performance) for a set of turns.
     */
    public function calculatePerformanceScore(array $turns, float $tMax = 2000.0): float
    {
        $n = count($turns);
        if ($n === 0) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($turns as $turn) {
            $latency = (float) ($turn['latency'] ?? 0.0);
            $intelligibility = (float) ($turn['intelligibility'] ?? $turn['confidence'] ?? $turn['clarity'] ?? 1.0);

            // Bounded between [0.0, 1.0] to guarantee stable index boundaries
            $latencyTerm = max(0.0, min(1.0, 1.0 - ($latency / $tMax)));
            $sum += $latencyTerm * $intelligibility;
        }

        return $sum / $n;
    }

    /**
     * Compute and save the performance score directly on the CallLog under scoped tenant context.
     */
    public function calculateAndSave(CallLog $callLog, array $turns, float $tMax = 2000.0): float
    {
        $tenant = $callLog->tenant;
        if ($tenant) {
            TenantScope::setTenantId($tenant->id);
        }

        try {
            $score = $this->calculatePerformanceScore($turns, $tMax);
            $callLog->performance_score = $score;
            $callLog->save();

            Log::info("Speech Performance Index computed for Call {$callLog->call_id}: {$score}");

            return $score;
        } finally {
            TenantScope::setTenantId(null);
        }
    }
}
