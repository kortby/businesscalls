<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;
use Twilio\TwiML\VoiceResponse;

class TelephonyFallbackController extends Controller
{
    /**
     * Handle Twilio dynamic TwiML fallback voice routing.
     */
    public function handle(Request $request, ?string $tenant_id = null)
    {
        // 1. Resolve tenant ID from route, query, or post parameters
        $resolvedTenantId = $tenant_id
            ?? $request->query('tenant_id')
            ?? $request->input('tenant_id');

        $tenant = null;
        if ($resolvedTenantId) {
            $tenant = Tenant::where('id', $resolvedTenantId)
                ->orWhere('slug', $resolvedTenantId)
                ->first();
        }

        // 2. Validate Twilio Request Signature
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->post();

        $token = $tenant ? ($tenant->getSetting('twilio_auth_token') ?? env('TWILIO_AUTH_TOKEN')) : env('TWILIO_AUTH_TOKEN');
        $validator = new RequestValidator($token ?? 'dummy-token');

        if (! $signature || ! $validator->validate($signature, $url, $params)) {
            // Log unauthorized signature verification failure
            AuditLog::create([
                'tenant_id' => $tenant?->id,
                'user_id' => null,
                'action' => 'signature_verification_failed',
                'ip_address' => $request->ip(),
                'browser_agent' => $request->userAgent(),
                'payload' => [
                    'url' => $url,
                    'params' => $params,
                    'signature' => $signature,
                    'error' => 'Twilio signature verification failed.',
                ],
            ]);

            return response('Signature verification failed', 403);
        }

        // 3. Resolve TwiML fallback parameters from tenant settings
        $greeting = $tenant ? ($tenant->getSetting('fallback_greeting') ?? 'Our primary voice channels are experiencing issues. Redirecting your call to our emergency line.') : 'Connection issues. Redirecting your call.';
        $audioUrl = $tenant ? ($tenant->getSetting('fallback_audio_url') ?? 'http://demo.twilio.com/docs/classic.mp3') : 'http://demo.twilio.com/docs/classic.mp3';
        $emergencyPhone = $tenant ? ($tenant->getSetting('emergency_phone') ?? $tenant->getSetting('emergency_phone_line') ?? '+15550009999') : '+15550009999';

        // 4. Generate Twilio TwiML Voice XML Response
        $response = new VoiceResponse;
        $response->say($greeting);
        $response->play($audioUrl);
        $response->dial($emergencyPhone);

        // 5. Log successful fallback route event
        AuditLog::create([
            'tenant_id' => $tenant?->id,
            'user_id' => null,
            'action' => 'telephony_fallback_routed',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'caller' => $request->input('From'),
                'to' => $request->input('To'),
                'emergency_phone' => $emergencyPhone,
                'status' => 200,
            ],
        ]);

        return response($response->asXML(), 200)
            ->header('Content-Type', 'text/xml');
    }
}
