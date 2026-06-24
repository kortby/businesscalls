<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeechPacingService
{
    /**
     * Evaluate the caller sentiment or call severity and dynamically swap the voice assistant settings mid-call.
     */
    public function evaluateAndSwapVoice(string $provider, string $callId, string $sentimentOrSeverity): array
    {
        $provider = strtolower($provider);
        $sentimentOrSeverity = strtolower($sentimentOrSeverity);

        // Determine target voice based on sentiment / severity
        $isEmergency = in_array($sentimentOrSeverity, ['emergency', 'distressed', 'angry', 'high_severity', 'panic']);

        $voiceConfig = $isEmergency
            ? [
                'provider' => 'elevenlabs',
                'voice_id' => 'AZnzlk1XvdvUeBnXmlld', // Domi (Reassuring)
                'vapi_voice_id' => 'AZnzlk1XvdvUeBnXmlld',
                'retell_voice_id' => '11labs-Domi',
                'stability' => 0.8,
                'similarity_boost' => 0.8,
                'style' => 0.1,
            ]
            : [
                'provider' => 'elevenlabs',
                'voice_id' => '21m00Tcm4TlvDq8ikWAM', // Rachel (Friendly)
                'vapi_voice_id' => '21m00Tcm4TlvDq8ikWAM',
                'retell_voice_id' => '11labs-Rachel',
                'stability' => 0.5,
                'similarity_boost' => 0.75,
                'style' => 0.0,
            ];

        $payload = [];
        $response = null;

        if ($provider === 'vapi') {
            $apiKey = env('VAPI_API_KEY', config('services.telephony.vapi_api_key', 'mock-vapi-key'));
            $url = "https://api.vapi.ai/call/{$callId}";

            $payload = [
                'assistantOverrides' => [
                    'voice' => [
                        'provider' => $voiceConfig['provider'],
                        'voiceId' => $voiceConfig['vapi_voice_id'],
                        'stability' => $voiceConfig['stability'],
                        'similarityBoost' => $voiceConfig['similarity_boost'],
                    ],
                ],
            ];

            try {
                $response = Http::withToken($apiKey)
                    ->patch($url, $payload);

                if (! $response->successful()) {
                    Log::error("Vapi voice swap failed for call {$callId}: ".$response->body());
                }
            } catch (\Exception $e) {
                Log::error('Exception swapping Vapi voice: '.$e->getMessage());
            }
        } elseif ($provider === 'retell') {
            $apiKey = env('RETELL_API_KEY', config('services.telephony.retell_api_key', 'mock-retell-key'));
            $url = "https://api.retellai.com/v2/calls/{$callId}";

            $payload = [
                'voice_id' => $voiceConfig['retell_voice_id'],
            ];

            try {
                $response = Http::withToken($apiKey)
                    ->post($url, $payload);

                if (! $response->successful()) {
                    Log::error("Retell voice swap failed for call {$callId}: ".$response->body());
                }
            } catch (\Exception $e) {
                Log::error('Exception swapping Retell voice: '.$e->getMessage());
            }
        }

        return [
            'success' => $response ? $response->successful() : false,
            'status' => $response ? $response->status() : null,
            'payload' => $payload,
            'voice_config' => $voiceConfig,
        ];
    }
}
