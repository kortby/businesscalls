<?php

namespace App\Services;

use App\Events\DispatchUpdated;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;

class VoicemailParserService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected LlmService $llmService
    ) {}

    /**
     * Parse voicemail transcripts to identify emergency job details.
     */
    public function parseVoicemail(CallLog $callLog): array
    {
        $transcript = $callLog->transcript;

        if (empty($transcript)) {
            return [
                'is_emergency' => false,
                'emergency_details' => 'Empty voicemail transcript.',
            ];
        }

        $systemPrompt = 'You are an emergency voicemail transcription parser. Analyze the voicemail transcript and identify the emergency job details. Provide a concise description of the emergency. If it is an emergency (like water leak, no heat, electrical sparks), mark it as emergency.';

        $messages = [
            ['role' => 'user', 'content' => "Voicemail transcript: \"{$transcript}\""],
        ];

        try {
            $parsedResult = $this->llmService->generateResponse($systemPrompt, $messages);

            // Check if transcript contains emergency indicators
            $lowerTranscript = strtolower($transcript);
            $emergencyKeywords = ['emergency', 'leak', 'burst', 'flood', 'spark', 'fire', 'no heat', 'no ac', 'clog', 'urgent', 'immediately'];
            $isEmergency = false;
            foreach ($emergencyKeywords as $keyword) {
                if (str_contains($lowerTranscript, $keyword)) {
                    $isEmergency = true;
                    break;
                }
            }

            // Dispatch high-priority update to administrators
            event(new DispatchUpdated($callLog->tenant_id, [
                'type' => 'emergency_voicemail',
                'message' => "🚨 Emergency Voicemail: {$parsedResult}",
                'call_id' => $callLog->call_id,
                'customer_phone' => $callLog->customer_phone,
                'recording_url' => $callLog->recording_url,
                'details' => $parsedResult,
                'is_emergency' => $isEmergency,
            ]));

            return [
                'is_emergency' => $isEmergency,
                'emergency_details' => $parsedResult,
            ];
        } catch (\Exception $e) {
            Log::error('Failed parsing voicemail transcript: '.$e->getMessage());

            return [
                'is_emergency' => false,
                'emergency_details' => 'Error parsing voicemail.',
            ];
        }
    }
}
