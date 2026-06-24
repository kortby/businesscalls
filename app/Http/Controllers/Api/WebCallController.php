<?php

namespace App\Http\Controllers\Api;

use App\Events\SupervisorBarged;
use App\Events\SupervisorWhisperSent;
use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Rules\ReCaptcha;
use App\Services\TenantSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $settingsService = app(TenantSettingsService::class);
        $assistantPayload = $settingsService->generateAssistantPayload($tenant);

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
                $callId = $response->json('call_id') ?? $response->json('id');
                if (! $accessToken) {
                    return response()->json(['error' => 'Missing access token in provider response.'], 500);
                }

                return response()->json([
                    'provider' => 'retell',
                    'access_token' => $accessToken,
                    'call_id' => $callId,
                    'assistant_id' => $assistantId,
                    'assistantOverrides' => $assistantPayload['assistantOverrides'] ?? [],
                    'assistant_overrides' => $assistantPayload['assistantOverrides'] ?? [],
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
            'assistantOverrides' => $assistantPayload['assistantOverrides'] ?? [],
            'assistant_overrides' => $assistantPayload['assistantOverrides'] ?? [],
        ]);
    }

    /**
     * Authenticate supervisor, exchange token for barge/monitor session, and broadcast Reverb event.
     */
    public function barge(Request $request): JsonResponse
    {
        $request->validate([
            'call_id' => 'required|string',
            'mode' => 'required|string|in:monitor,barge',
        ]);

        $user = $request->user();

        if (! $user || ! $user->isSupervisor()) {
            return response()->json(['error' => 'Forbidden. Supervisor permissions required.'], 403);
        }

        $tenant = $user->tenant;
        $callId = $request->input('call_id');
        $mode = $request->input('mode');

        Log::info("Supervisor {$user->name} requesting barge/monitor on Call: {$callId}, mode: {$mode}");

        $callLog = CallLog::where('call_id', $callId)->first();
        if (! $callLog) {
            return response()->json(['error' => 'Active call session not found.'], 404);
        }

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        if ($tenant->is_test_mode) {
            $accessToken = "sandbox-supervisor-{$provider}-token-".Str::random(10);
            $roomUrl = "https://api.{$provider}.ai/mock-barge-room/{$callId}";
        } else {
            if ($provider === 'retell') {
                try {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post("https://api.retellai.com/barge-call/{$callId}", [
                            'mode' => $mode,
                        ]);

                    if ($response->failed()) {
                        Log::error('Retell barge failed: '.$response->body());

                        return response()->json(['error' => 'Retell barge swap failed.'], 500);
                    }

                    $accessToken = $response->json('access_token');
                    $roomUrl = $response->json('room_url') ?? "https://api.retellai.com/room/{$callId}";
                } catch (\Exception $e) {
                    Log::error('Retell barge swap exception: '.$e->getMessage());

                    return response()->json(['error' => 'Retell connection timed out.'], 504);
                }
            } else {
                try {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post("https://api.vapi.ai/call/{$callId}/barge", [
                            'mode' => $mode,
                        ]);

                    if ($response->failed()) {
                        Log::error('Vapi barge failed: '.$response->body());

                        return response()->json(['error' => 'Vapi barge swap failed.'], 500);
                    }

                    $accessToken = env('VAPI_PUBLIC_KEY') ?? 'dummy-vapi-public-key';
                    $roomUrl = $response->json('room_url') ?? "https://live.vapi.ai/room/{$callId}";
                } catch (\Exception $e) {
                    Log::error('Vapi barge exception: '.$e->getMessage());

                    return response()->json(['error' => 'Vapi connection timed out.'], 504);
                }
            }
        }

        $callLog->update([
            'call_end_reason' => $mode === 'barge' ? 'supervisor_barged' : $callLog->call_end_reason,
        ]);

        event(new SupervisorBarged($tenant->id, $callId, $mode, $user->name));

        return response()->json([
            'success' => true,
            'provider' => $provider,
            'access_token' => $accessToken,
            'room_url' => $roomUrl,
            'call_id' => $callId,
            'mode' => $mode,
        ]);
    }

    /**
     * Broadcast a supervisor whisper coaching event to the active technician.
     */
    public function whisper(Request $request): JsonResponse
    {
        $request->validate([
            'call_id' => 'required|string',
            'instruction' => 'required|string',
        ]);

        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized or missing tenant context.'], 403);
        }

        // Sanitize the instruction
        $instruction = strip_tags($request->input('instruction'));

        // Broadcast the whisper event
        event(new SupervisorWhisperSent(
            $user->tenant_id,
            $request->input('call_id'),
            $instruction,
            $user->name
        ));

        return response()->json([
            'success' => true,
            'message' => 'Whisper coaching tip sent.',
        ]);
    }
}
