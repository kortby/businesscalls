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
        $customInstructions = $tenant->getSetting('ai_prompt', 'Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.');
        $emergencyFee = $tenant->getSetting('emergency_fee', '$150');

        $skills = $tenant->employees()->get()->pluck('skills')->flatten()->filter()->unique()->implode(', ');

        return [
            'assistantOverrides' => [
                'variableValues' => [
                    'business_name' => $businessName,
                    'custom_instructions' => $customInstructions,
                    'emergency_fee' => $emergencyFee,
                    'service_list' => $skills ?: 'General Contracting',
                ],
            ],
        ];
    }

    /**
     * Get the default system prompt incorporating dynamic placeholder variables.
     */
    public function getDefaultSystemPrompt(): string
    {
        return 'You are the AI voice dispatcher for {{business_name}}. Please act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings. Your custom instructions: {{custom_instructions}}. The emergency fee for after-hours calls is {{emergency_fee}}. We specialize in and support these services: {{service_list}}.';
    }
}
