<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Text;
use Symfony\Component\HttpFoundation\Response;

class LanguageDetectionMiddleware
{
    /**
     * Handle an incoming request and check if caller speaks Spanish or French.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $transcript = $request->input('message.transcript')
            ?? $request->input('transcript')
            ?? $request->input('message.toolCalls.0.function.arguments.transcript')
            ?? $request->input('message.transcript.transcript')
            ?? '';

        if (! empty($transcript)) {
            $prompt = "You are a language detection utility. Analyze the following transcript chunk: '{$transcript}'. Determine if the spoken language is mainly English, Spanish (es), or French (fr). Respond with ONLY the 2-letter ISO code: 'en', 'es', or 'fr'. Do not add any punctuation or explanatory words.";

            try {
                $detectedLanguage = strtolower(trim(Text::prompt($prompt)));

                if (in_array($detectedLanguage, ['es', 'fr'])) {
                    $callId = $request->input('call_id')
                        ?? $request->input('call.id')
                        ?? $request->input('message.call.id')
                        ?? $request->input('message.callId');

                    if ($callId) {
                        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
                        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

                        Log::info("Language change detected: Swapped active transcriber for Call {$callId} to: {$detectedLanguage}");

                        if ($provider === 'vapi') {
                            Http::withToken($apiKey)->timeout(5)->patch("https://api.vapi.ai/call/{$callId}", [
                                'transcriber' => [
                                    'language' => $detectedLanguage,
                                ],
                                'pronunciation_dictionary' => [
                                    'language' => $detectedLanguage,
                                    'entries' => [
                                        ['original' => 'hello', 'pronunciation' => $detectedLanguage === 'es' ? 'hola' : 'bonjour'],
                                    ],
                                ],
                            ]);
                        } else {
                            Http::withToken($apiKey)->timeout(5)->patch("https://api.retellai.com/v2/calls/{$callId}", [
                                'transcriber' => [
                                    'language' => $detectedLanguage,
                                ],
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('LanguageDetectionMiddleware error: '.$e->getMessage());
            }
        }

        return $next($request);
    }
}
