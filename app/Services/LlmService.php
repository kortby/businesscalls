<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmService
{
    /**
     * Generate a completion using the configured LLM API.
     *
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function generateResponse(string $systemPrompt, array $messages): string
    {
        $apiKey = env('LLM_API_KEY') ?: env('OPENAI_API_KEY') ?: 'dummy-key';

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('LLM_MODEL', 'gpt-4o-mini'),
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $messages
                    ),
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Hello! How can I help you today?';
            }

            Log::warning('LLM service API returned error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('LLM service request failed: '.$e->getMessage());
        }

        // Fallback response for offline/testing mode
        return 'Thank you for your message. We have received it and will get back to you shortly.';
    }
}
