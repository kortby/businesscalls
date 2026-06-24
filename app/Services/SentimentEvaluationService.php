<?php

namespace App\Services;

use App\Events\SupervisorBarged;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentEvaluationService
{
    /**
     * Evaluate the sentiment score of a call session and trigger supervisor routing if threshold breached.
     */
    public function evaluateTurn(string $callId, string $text, int $tenantId): float
    {
        $tenant = Tenant::find($tenantId);
        $urgencyWeights = $tenant?->getSetting('urgency_weights') ?? [
            'gas leak' => 2.5,
            'sewer' => 2.0,
            'emergency' => 1.8,
            'burst pipe' => 2.2,
            'flooding' => 2.0,
            'fire' => 3.0,
        ];

        // 1. Fetch historical turns for this call
        $cacheKey = "call-sentiment-turns:{$callId}";
        $turns = Cache::get($cacheKey, []);

        // 2. Evaluate current turn sentiment (Phi) between -1.0 and +1.0
        $phi = $this->calculatePhi($text);

        // 3. Determine urgency multiplier (omega)
        $omega = 1.0;
        foreach ($urgencyWeights as $keyword => $weight) {
            if (stripos($text, $keyword) !== false) {
                $omega = max($omega, (float) $weight);
            }
        }

        // Add current turn to history
        $turns[] = [
            'text' => $text,
            'phi' => $phi,
            'omega' => $omega,
        ];
        Cache::put($cacheKey, $turns, 600);

        // 4. Calculate total score and active distress decay (gamma)
        $T = count($turns);
        $sum = 0.0;
        $gammaDistress = 0.0;

        foreach ($turns as $turn) {
            $turnPhi = $turn['phi'];
            $turnOmega = $turn['omega'];

            // Accumulate turn sentiment * urgency weight
            $sum += ($turnPhi * $turnOmega);

            // Active distress decay model based on sustained caller stress
            if ($turnPhi < -0.3) {
                $gammaDistress += 0.15;
            } else {
                $gammaDistress -= 0.05;
            }
            // Keep gamma bounded between 0.0 and 1.0
            $gammaDistress = max(0.0, min(1.0, $gammaDistress));
        }

        $avgWeightedSentiment = $sum / $T;
        $omegaSentiment = $avgWeightedSentiment - $gammaDistress;

        Log::info("Calculated Omega_sentiment for Call {$callId}: {$omegaSentiment} (Turns: {$T}, Distress: {$gammaDistress})");

        // 5. Trigger supervisor escalation if score drops below -0.65
        if ($omegaSentiment < -0.65) {
            $this->escalateToSupervisor($tenantId, $callId, $omegaSentiment);
        }

        return $omegaSentiment;
    }

    /**
     * Basic lexical sentiment analyzer return value between -1.0 and +1.0.
     */
    protected function calculatePhi(string $text): float
    {
        $negativeKeywords = [
            'angry', 'mad', 'furious', 'upset', 'broken', 'disaster', 'awful', 'terrible',
            'worst', 'leak', 'leaking', 'wrong', 'fail', 'emergency', 'help', 'no', 'stop',
            'hate', 'annoyed', 'useless', 'garbage', 'crap', 'gas', 'smell', 'odor',
        ];
        $positiveKeywords = [
            'happy', 'good', 'thanks', 'thank you', 'perfect', 'great', 'excellent',
            'appreciate', 'yes', 'awesome', 'solved', 'working', 'wonderful', 'helper',
        ];

        $text = strtolower($text);
        $negCount = 0;
        $posCount = 0;

        foreach ($negativeKeywords as $word) {
            if (str_contains($text, $word)) {
                $negCount++;
            }
        }
        foreach ($positiveKeywords as $word) {
            if (str_contains($text, $word)) {
                $posCount++;
            }
        }

        if ($negCount === 0 && $posCount === 0) {
            return 0.0; // Neutral
        }

        // Compute proportional score
        $total = $negCount + $posCount;
        $phi = ($posCount - $negCount) / $total;

        return max(-1.0, min(1.0, (float) $phi));
    }

    /**
     * Trigger live WebRTC routing directly to supervisor barging view.
     */
    protected function escalateToSupervisor(int $tenantId, string $callId, float $score): void
    {
        Log::critical("Supervisor Escalation Triggered for Call: {$callId} (Score: {$score})");

        // Send API PATCH to telephony provider to mute/bypass AI assistant
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        try {
            if ($provider === 'vapi') {
                Http::withToken($apiKey)->post("https://api.vapi.ai/call/{$callId}/barge", [
                    'mode' => 'barge',
                    'escalation' => true,
                ]);
            } else {
                Http::withToken($apiKey)->post("https://api.retellai.com/barge-call/{$callId}", [
                    'mode' => 'barge',
                    'escalation' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed call transfer API route for Call ID: {$callId}: ".$e->getMessage());
        }

        // Broadcast Reverb visual update so supervisor UI automatically hooks WebRTC stream
        event(new SupervisorBarged($tenantId, $callId, 'barge', 'Auto-Escalation System'));
    }
}
