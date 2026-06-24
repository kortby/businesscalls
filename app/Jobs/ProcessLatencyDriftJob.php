<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Interruptible;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessLatencyDriftJob implements Interruptible, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $tenantId,
        public string $callId,
        public array $telemetry
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Calculate Conversational Latency Drift Index
        // Omega_drift = 1/M * sum(audio_out - audio_in) - delta_target (600ms)
        $turns = $this->telemetry['turns'] ?? $this->telemetry;
        if (! is_array($turns) || empty($turns)) {
            Log::warning("No conversational turns available in telemetry for Call ID: {$this->callId}");

            return;
        }

        $sum = 0.0;
        $M = count($turns);
        foreach ($turns as $turn) {
            $isMsKey = isset($turn['audio_in_ms']) || isset($turn['audio_out_ms']);

            $audioIn = $turn['audio_in_ms'] ?? $turn['audio_in'] ?? 0.0;
            $audioOut = $turn['audio_out_ms'] ?? $turn['audio_out'] ?? 0.0;

            if (! $isMsKey) {
                // Convert to milliseconds if timestamps are in seconds (e.g., 5.2s rather than 5200ms)
                if ($audioIn < 100000.0) {
                    $audioIn *= 1000.0;
                }
                if ($audioOut < 100000.0) {
                    $audioOut *= 1000.0;
                }
            }

            $sum += ($audioOut - $audioIn);
        }

        $avg = $sum / $M;
        $deltaTarget = 600.0; // 600ms
        $omegaDrift = $avg - $deltaTarget;

        // 2. Save latency drift under CallLog context
        $callLog = CallLog::where('call_id', $this->callId)->first();
        if ($callLog) {
            $callLog->latency_drift = $omegaDrift;
            $callLog->save();
        } else {
            Log::warning("CallLog not found for Call ID: {$this->callId}");
        }

        // 3. Alert Escalation Trigger check
        // If Omega_drift exceeds 1200ms across 3 consecutive calls for this tenant
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $recentCalls = CallLog::where('tenant_id', $this->tenantId)
            ->whereNotNull('latency_drift')
            ->latest()
            ->take(3)
            ->get();

        if ($recentCalls->count() === 3) {
            $consecutiveSpike = true;
            foreach ($recentCalls as $call) {
                if ($call->latency_drift <= 1200.0) {
                    $consecutiveSpike = false;
                    break;
                }
            }

            if ($consecutiveSpike) {
                $this->triggerAlertEscalation($tenant, $recentCalls, $omegaDrift);
            }
        }
    }

    /**
     * Trigger incident reporting and warning webhook.
     */
    protected function triggerAlertEscalation(Tenant $tenant, $recentCalls, float $currentDrift): void
    {
        $webhookUrl = $tenant->getSetting('warning_webhook_url')
            ?? env('WARNING_WEBHOOK_URL')
            ?? 'https://api.vapi.ai/latency-warning';

        $incidentPayload = [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'alert_type' => 'high_latency_drift',
            'calculated_drift_ms' => $currentDrift,
            'threshold_ms' => 1200.0,
            'recent_drifts' => $recentCalls->pluck('latency_drift')->toArray(),
            'call_ids' => $recentCalls->pluck('call_id')->toArray(),
            'message' => 'Conversational latency drift exceeded 1200ms across 3 consecutive calls.',
        ];

        // Outgoing alert webhook call
        try {
            Http::timeout(5)->post($webhookUrl, $incidentPayload);
        } catch (\Exception $e) {
            Log::error("Failed to send latency alert webhook for Tenant ID: {$tenant->id}: ".$e->getMessage());
        }

        // High priority incident audit log under tenant context
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'high_priority_incident',
            'payload' => $incidentPayload,
        ]);

        Log::error("High priority latency incident report logged for Tenant ID: {$tenant->id}");
    }

    /**
     * Handle the interruption signal.
     */
    public function interrupted(int $signal): void
    {
        Log::info("ProcessLatencyDriftJob interrupted by signal {$signal} for Call ID: {$this->callId}");
    }
}
