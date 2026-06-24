<?php

namespace App\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Text
{
    /**
     * Mock response for testing.
     */
    public static ?string $mockResponse = null;

    /**
     * Perform zero-shot entity extraction prompting.
     */
    public static function prompt(string $prompt): string
    {
        if (static::$mockResponse !== null) {
            return static::$mockResponse;
        }

        $apiKey = env('LLM_API_KEY') ?: env('OPENAI_API_KEY') ?: 'dummy-key';
        $model = env('LLM_MODEL', 'gpt-4o-mini');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? '{}';
            }

            Log::error('Laravel AI Text prompt failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Laravel AI Text prompt exception: '.$e->getMessage());
        }

        return '{}';
    }
}
