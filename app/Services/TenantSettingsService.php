<?php

namespace App\Services;

use App\Models\Tenant;

class TenantSettingsService
{
    /**
     * Generate the dynamic Vapi assistantOverrides payload for a tenant.
     */
    public function generateAssistantPayload(Tenant $tenant): array
    {
        $businessName = $tenant->name;
        $variant = request()->attributes->get('active_experiment_variant');
        $customInstructions = $variant
            ? $variant->prompt_instructions
            : $tenant->getSetting('ai_prompt', 'Speak briskly, concisely, and directly in 1-2 short sentences. Get straight to the chase without unnecessary pleasantries or filler. When a customer wants to book or schedule, offer the first available appointment right away. If the customer does not like that time, ask what day and time they prefer. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.');
        $emergencyFee = $tenant->getSetting('emergency_fee', '$150');

        $skills = $tenant->employees()->get()->pluck('skills')->flatten()->filter()->unique()->implode(', ');

        $startSpeakingVal = (int) $tenant->getSetting('startSpeakingPlan', 600);
        $stopSpeakingVal = (float) $tenant->getSetting('stopSpeakingPlan', 0.2);
        $backchannelEnabled = (bool) $tenant->getSetting('backchanneling_enabled', false);
        $voiceSpeed = (float) $tenant->getSetting('voice_speed', 1.25);

        $payload = [
            'assistantOverrides' => [
                'voice' => [
                    'speed' => $voiceSpeed,
                ],
                'variableValues' => [
                    'business_name' => $businessName,
                    'custom_instructions' => $customInstructions,
                    'emergency_fee' => $emergencyFee,
                    'service_list' => $skills ?: 'General Contracting',
                ],
                'startSpeakingPlan' => [
                    'waitSeconds' => (float) ($startSpeakingVal / 1000.0),
                ],
                'stopSpeakingPlan' => [
                    'numWords' => 0,
                    'voiceSeconds' => $stopSpeakingVal,
                    'backoffSeconds' => 1.0,
                ],
                'backchannelingEnabled' => $backchannelEnabled,
            ],
        ];

        // Apply audio denoising configurations if activated
        if ($tenant->getSetting('background_denoising_enabled', false)) {
            $payload['assistantOverrides']['backgroundDenoisingEnabled'] = true;
            $payload['assistantOverrides']['noiseSuppressionEnabled'] = true;
            $payload['assistantOverrides']['advancedDenoising'] = true;
        }

        // Apply A/B Experiment metadata and model overrides
        if ($variant) {
            $payload['assistantOverrides']['metadata'] = [
                'experiment_variant_id' => $variant->id,
            ];
            $payload['metadata'] = [
                'experiment_variant_id' => $variant->id,
            ];

            if ($variant->model_provider) {
                $parts = explode('/', $variant->model_provider);
                if (count($parts) === 2) {
                    $payload['assistantOverrides']['model'] = [
                        'provider' => $parts[0],
                        'model' => $parts[1],
                    ];
                } else {
                    $payload['assistantOverrides']['model'] = [
                        'model' => $variant->model_provider,
                    ];
                }
            }
        }

        $dictionaryService = app(PronunciationDictionaryService::class);
        $payload = $dictionaryService->applyOverridesToPayload($tenant, $payload);

        $complianceService = app(ComplianceSanitizerService::class);
        $complianceService->applyCompliance($tenant, $payload);

        return $payload;
    }

    /**
     * Get the default system prompt incorporating dynamic placeholder variables.
     */
    public function getDefaultSystemPrompt(): string
    {
        return 'You are the AI voice dispatcher for {{business_name}}. Speak briskly and concisely in 1-2 short, direct sentences. Get straight to the chase without filler phrases, lengthy pleasantries, or repetition. When a customer wants an appointment, offer the first available opening right away. If they decline or prefer a different time, ask what day and time works best for them. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings. Your custom instructions: {{custom_instructions}}. The emergency fee for after-hours calls is {{emergency_fee}}. We specialize in and support these services: {{service_list}}.';
    }
}
