<?php

namespace App\Jobs;

use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateVoiceQualityJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CallLog $callLog
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tenant = $this->callLog->tenant;
        if (! $tenant) {
            Log::warning('EvaluateVoiceQualityJob aborted: CallLog does not belong to a valid tenant.');

            return;
        }

        // Apply tenant database scoping inside queue worker thread
        TenantScope::setTenantId($tenant->id);

        try {
            // Retrieve metrics from CallLog or default values if null
            $intelligibility = $this->callLog->acoustic_intelligibility ?? 0.95;
            $ttsDelay = $this->callLog->latency ?? 250;
            $emotion = $this->callLog->vocal_inflection_variance ?? 0.85;

            // Load weights from config file
            $alpha = (float) config('telephony.mos_weights.alpha', 0.4);
            $beta = (float) config('telephony.mos_weights.beta', 0.3);
            $gamma = (float) config('telephony.mos_weights.gamma', 0.3);

            // Compute latency term: (1 - L_tts / 1500) bounded below by 0
            $latencyTerm = max(0.0, 1.0 - ($ttsDelay / 1500.0));

            // Compute MOS Prediction Index (Ψ_MOS)
            $mos = ($alpha * $intelligibility) + ($beta * $latencyTerm) + ($gamma * $emotion);

            // Update call log record
            $this->callLog->acoustic_intelligibility = $intelligibility;
            $this->callLog->vocal_inflection_variance = $emotion;
            $this->callLog->mos_score = $mos;
            $this->callLog->save();

            Log::info("Voice Quality evaluated for Call ID: {$this->callLog->call_id}. MOS: {$mos}");
        } catch (\Exception $e) {
            Log::error('EvaluateVoiceQualityJob failed with exception: '.$e->getMessage());
        } finally {
            // Always reset the tenant database scope
            TenantScope::setTenantId(null);
        }
    }
}
