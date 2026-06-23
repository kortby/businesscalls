<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Rules\ReCaptcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebCallController extends Controller
{
    /**
     * Generate an ephemeral client access token or public key payload for WebRTC.
     */
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'recaptcha_token' => [
                app()->environment('testing') ? 'nullable' : 'required',
                new ReCaptcha,
            ],
        ]);

        $user = $request->user();

        if (! $user || ! $user->tenant) {
            return response()->json(['error' => 'Unauthorized or missing active tenant.'], 403);
        }

        $tenant = $user->tenant;
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';
        $assistantId = $tenant->getSetting('voice_assistant_id') ?? 'default-assistant-id';

        Log::info("Generating WebRTC calling token for Tenant: {$tenant->id}, provider: {$provider}");

        if ($provider === 'retell') {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.retellai.com/create-web-call', [
                        'assistant_id' => $assistantId,
                    ]);

                if ($response->failed()) {
                    Log::error('Retell create-web-call failed: '.$response->body());

                    return response()->json(['error' => 'Telephony provider session failed.'], 500);
                }

                $accessToken = $response->json('access_token');
                if (! $accessToken) {
                    return response()->json(['error' => 'Missing access token in provider response.'], 500);
                }

                return response()->json([
                    'provider' => 'retell',
                    'access_token' => $accessToken,
                    'assistant_id' => $assistantId,
                ]);
            } catch (\Exception $e) {
                Log::error('Retell web call connection exception: '.$e->getMessage());

                return response()->json(['error' => 'Telephony provider timed out.'], 504);
            }
        }

        // For Vapi, the public key is used as the client access token
        $publicKey = env('VAPI_PUBLIC_KEY') ?? 'dummy-vapi-public-key';

        return response()->json([
            'provider' => 'vapi',
            'access_token' => $publicKey,
            'assistant_id' => $assistantId,
        ]);
    }
}
