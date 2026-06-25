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
            : $tenant->getSetting('ai_prompt', 'Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.');
        $emergencyFee = $tenant->getSetting('emergency_fee', '$150');

        $skills = $tenant->employees()->get()->pluck('skills')->flatten()->filter()->unique()->implode(', ');

        $startSpeakingVal = (int) $tenant->getSetting('startSpeakingPlan', 600);
        $stopSpeakingVal = (float) $tenant->getSetting('stopSpeakingPlan', 0.2);

        $payload = [
            'assistantOverrides' => [
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

        return $payload;
    }

    /**
     * Get the default system prompt incorporating dynamic placeholder variables.
     */
    public function getDefaultSystemPrompt(): string
    {
        return 'You are the AI voice dispatcher for {{business_name}}. Please act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings. Your custom instructions: {{custom_instructions}}. The emergency fee for after-hours calls is {{emergency_fee}}. We specialize in and support these services: {{service_list}}.';
    }
}
