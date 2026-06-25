<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrandedCallerIdService
{
    /**
     * Register the tenant's outbound phone lines for Branded Calling.
     *
     * @throws \Exception
     */
    public function registerBrandedCallerId(Tenant $tenant, array $businessData): array
    {
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));
        $apiKey = env('TELEPHONY_API_KEY') ?? 'dummy-telephony-api-key';

        Log::info("Initiating Branded Caller ID registration for Tenant: {$tenant->id}, provider: {$provider}");

        $legalName = $businessData['legal_business_name'] ?? $tenant->name;
        $logoUrl = $businessData['brand_logo_url'] ?? '';
        $physicalAddress = $businessData['physical_address'] ?? '123 Main St';
        $phoneNumbers = $businessData['phone_numbers'] ?? [];

        if (empty($phoneNumbers)) {
            $existingPhone = $tenant->getSetting('telephony_phone_number');
            if ($existingPhone) {
                $phoneNumbers = [$existingPhone];
            }
        }

        try {
            if ($provider === 'vapi') {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.vapi.ai/branded-caller-id', [
                        'businessName' => $legalName,
                        'logoUrl' => $logoUrl,
                        'address' => $physicalAddress,
                        'phoneNumbers' => $phoneNumbers,
                    ]);
            } else {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.retellai.com/v2/register-branded-caller-id', [
                        'business_name' => $legalName,
                        'logo_url' => $logoUrl,
                        'physical_address' => $physicalAddress,
                        'phone_numbers' => $phoneNumbers,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Branded Caller ID registration request failed: '.$e->getMessage());
            throw new \Exception('Telephony provider request failed: '.$e->getMessage());
        }

        if ($response->failed()) {
            Log::error('Branded Caller ID registration failed: '.$response->body());
            throw new \Exception('Telephony provider error: '.($response->json('message') ?? $response->json('error') ?? $response->body()));
        }

        $trunkId = $response->json('trunk_id') ?? $response->json('id') ?? 'trunk_mock_'.uniqid();

        // Update settings table
        $settings = $tenant->settings ?? [];
        $settings['branded_caller_id_trunk_id'] = $trunkId;
        $settings['branded_caller_id_status'] = 'verified';
        $settings['branded_business_name'] = $legalName;
        $settings['branded_logo_url'] = $logoUrl;
        $tenant->settings = $settings;
        $tenant->save();

        Log::info("Branded Caller ID registered successfully. Trunk ID: {$trunkId}");

        return [
            'status' => 'success',
            'trunk_id' => $trunkId,
            'business_name' => $legalName,
            'logo_url' => $logoUrl,
        ];
    }
}
