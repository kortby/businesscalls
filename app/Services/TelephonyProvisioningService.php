<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelephonyProvisioningService
{
    /**
     * Programmatically search for and purchase a phone number for a tenant.
     *
     * @return array{phone_number: string, phone_number_id: string}
     *
     * @throws \Exception
     */
    public function purchasePhoneNumber(Tenant $tenant, string $areaCode): array
    {
        if (! preg_match('/^\d{3}$/', $areaCode)) {
            throw new \InvalidArgumentException('Area code must be exactly 3 digits.');
        }

        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        $assistantId = $tenant->getSetting('voice_assistant_id') ?? 'default-assistant-id';

        Log::info("Initiating telephony purchase for Tenant: {$tenant->id} ({$tenant->name}), provider: {$provider}, Area Code: {$areaCode}");

        try {
            if ($provider === 'vapi') {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.vapi.ai/phone-number', [
                        'provider' => 'twilio',
                        'areaCode' => $areaCode,
                        'assistantId' => $assistantId,
                    ]);
            } else {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.retellai.com/buy-phone-number', [
                        'area_code' => (int) $areaCode,
                        'assistant_id' => $assistantId,
                    ]);
            }
        } catch (ConnectionException $e) {
            Log::error('Telephony provisioning request timed out: '.$e->getMessage());
            throw new \Exception('Telephony provider request timed out: '.$e->getMessage());
        }

        $body = $response->body();

        if ($response->failed()) {
            Log::error('Telephony provisioning failed: '.$body);

            // Defensive check for billing errors or balance limits
            $lowerBody = strtolower($body);
            $billingKeywords = ['billing', 'balance', 'credit', 'payment', 'funds', 'limit', 'charge'];
            foreach ($billingKeywords as $keyword) {
                if (str_contains($lowerBody, $keyword)) {
                    throw new \Exception('Telephony billing error: '.($response->json('message') ?? $response->json('error') ?? $body));
                }
            }

            throw new \Exception('Telephony provider error: '.($response->json('message') ?? $response->json('error') ?? $body));
        }

        $phoneId = $response->json('id') ?? $response->json('phone_number_id');
        $phoneNumber = $response->json('number') ?? $response->json('phone_number');

        if (! $phoneId || ! $phoneNumber) {
            throw new \Exception('Telephony response is missing phone line credentials.');
        }

        // Update settings table securely within transaction
        DB::transaction(function () use ($tenant, $phoneId, $phoneNumber) {
            $settings = $tenant->settings ?? [];
            $settings['telephony_phone_number_id'] = $phoneId;
            $settings['telephony_phone_number'] = $phoneNumber;
            $tenant->settings = $settings;
            $tenant->save();
        });

        Log::info("Telephony line {$phoneNumber} (ID: {$phoneId}) purchased and associated successfully.");

        return [
            'phone_number' => $phoneNumber,
            'phone_number_id' => $phoneId,
        ];
    }
}
