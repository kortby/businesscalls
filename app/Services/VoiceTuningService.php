<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceTuningService
{
    /**
     * Calibrate synthesizer settings mid-call depending on customer sentiment and keyword distress flags.
     */
    public function calibrateVoice(Tenant $tenant, string $callId, string $sentiment, array $keywords, string $provider): array
    {
        $distressKeywords = ['flood', 'fire', 'leak', 'burst', 'emergency', 'outage', 'broken', 'accident', 'danger', 'water'];
        $hasDistressKeyword = false;

        foreach ($keywords as $kw) {
            if (in_array(strtolower($kw), $distressKeywords)) {
                $hasDistressKeyword = true;
                break;
            }
        }

        $isDistressed = (strtolower($sentiment) === 'distressed' || strtolower($sentiment) === 'angry' || $hasDistressKeyword);

        if (! $isDistressed) {
            return [
                'updated' => false,
                'message' => 'Voice tuning normal. Sentiment within baseline parameters.',
            ];
        }

        Log::info("Distress detected on Call {$callId}. Sentiment: {$sentiment}. Adjusting voice synthesizer overrides for optimal empathy.");

        $overrides = [];
        if ($provider === 'cartesia') {
            $overrides = [
                'voice_provider' => 'cartesia',
                'speed' => 0.90, // lower speed for empathetic, calming delivery
                'emotion' => ['calm' => 0.8, 'positivity' => 0.4],
            ];
        } else {
            // ElevenLabs
            $overrides = [
                'voice_provider' => 'elevenlabs',
                'stability' => 0.85, // higher stability for clear, steady voice clone
                'similarity_boost' => 0.75,
                'style' => 0.1,
            ];
        }

        $success = false;

        if ($tenant->is_test_mode || app()->environment('testing')) {
            $success = true;
        } else {
            $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
            if ($provider === 'retell') {
                try {
                    $response = Http::withToken($apiKey)
                        ->timeout(5)
                        ->patch("https://api.retellai.com/update-call/{$callId}", [
                            'voice_settings' => $overrides,
                        ]);
                    $success = $response->successful();
                } catch (\Exception $e) {
                    Log::error('Retell voice setting override exception: '.$e->getMessage());
                    $success = false;
                }
            } else {
                // Vapi
                try {
                    $response = Http::withToken($apiKey)
                        ->timeout(5)
                        ->patch("https://api.vapi.ai/call/{$callId}", [
                            'assistant' => [
                                'voice' => [
                                    'provider' => $provider,
                                    'voiceId' => 'empathetic-fallback-voice-id',
                                    'stability' => $overrides['stability'] ?? null,
                                    'speed' => $overrides['speed'] ?? null,
                                ],
                            ],
                        ]);
                    $success = $response->successful();
                } catch (\Exception $e) {
                    Log::error('Vapi voice setting override exception: '.$e->getMessage());
                    $success = false;
                }
            }
        }

        return [
            'updated' => true,
            'success' => $success,
            'overrides' => $overrides,
        ];
    }
}
