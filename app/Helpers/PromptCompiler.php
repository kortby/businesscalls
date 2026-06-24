<?php

namespace App\Helpers;

use App\Models\Customer;
use App\Models\Tenant;

class PromptCompiler
{
    /**
     * Compile a prompt template string by injecting variables from Tenant and Customer.
     *
     * @param  array<string, mixed>  $extraVariables
     */
    public static function compile(
        string $template,
        Tenant $tenant,
        ?Customer $customer = null,
        array $extraVariables = []
    ): string {
        $parts = explode(' ', $customer->name ?? '', 2);
        $firstName = $parts[0] ?? 'Valued Customer';
        $lastName = $parts[1] ?? '';

        $variables = array_merge([
            'business_name' => $tenant->name,
            'emergency_fee' => $tenant->getSetting('emergency_fee', '$150'),
            'emergency_rules' => $tenant->getSetting('emergency_rules', ''),
            'custom_instructions' => $tenant->getSetting('ai_prompt', ''),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'customer_phone' => $customer->phone ?? 'Unknown',
            'customer_email' => $customer->email ?? '',
            'language' => $customer->language ?? 'en',
        ], $extraVariables);

        // Substitute placeholders
        $compiled = $template;
        foreach ($variables as $key => $val) {
            $compiled = str_replace('{{'.$key.'}}', (string) $val, $compiled);
        }

        // Apply Multilingual Rules
        $lang = strtolower($variables['language']);
        if ($lang === 'es' || $lang === 'spanish') {
            $compiled .= "\n[System Instruction: The customer prefers Spanish. Conduct the conversation entirely in Spanish. Traduzca sus respuestas al español.]";
        }

        return $compiled;
    }
}
