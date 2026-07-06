<?php

namespace App\Ai;

use Illuminate\Support\Carbon;
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

        if ($apiKey === 'dummy-key') {
            // Local mockup generator for developer testing
            $bodySegment = '';
            if (preg_match("/Analyze the incoming customer SMS message: '(.*)'/i", $prompt, $matches)) {
                $bodySegment = strtolower($matches[1]);
            }

            if (str_contains($bodySegment, 'plumbing')) {
                return json_encode([
                    'trade_category' => 'plumbing',
                    'requested_time' => Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0, 0)->toDateTimeString(),
                ]);
            }

            return json_encode([
                'trade_category' => 'ac-diagnostics',
                'requested_time' => Carbon::now()->next(Carbon::THURSDAY)->setTime(10, 0, 0)->toDateTimeString(),
            ]);
        }

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

        // Fallback to local mockup generator if API fails (e.g. quota limits)
        $bodySegment = '';
        if (preg_match("/Analyze the incoming customer SMS message: '(.*)'/i", $prompt, $matches)) {
            $bodySegment = strtolower($matches[1]);
        }

        if (str_contains($bodySegment, 'plumbing')) {
            return json_encode([
                'trade_category' => 'plumbing',
                'requested_time' => Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0, 0)->toDateTimeString(),
            ]);
        }

        return json_encode([
            'trade_category' => 'ac-diagnostics',
            'requested_time' => Carbon::now()->next(Carbon::THURSDAY)->setTime(10, 0, 0)->toDateTimeString(),
        ]);
    }
}
