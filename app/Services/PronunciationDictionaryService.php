<?php

namespace App\Services;

use App\Models\Tenant;

class PronunciationDictionaryService
{
    /**
     * Register a custom phonetic spelling for a word under the tenant settings.
     */
    public function registerPhoneticSpelling(Tenant $tenant, string $word, string $phonetic): void
    {
        $settings = $tenant->settings ?? [];
        $dictionary = $settings['phonetic_dictionary'] ?? [];
        $dictionary[trim($word)] = trim($phonetic);
        $settings['phonetic_dictionary'] = $dictionary;
        $tenant->settings = $settings;
        $tenant->save();
    }

    /**
     * Retrieve the phonetic dictionary array for a tenant.
     *
     * @return array<string, string>
     */
    public function getPhoneticDictionary(Tenant $tenant): array
    {
        return $tenant->getSetting('phonetic_dictionary', []);
    }

    /**
     * Dynamically merge phonetic overrides into the assistant overrides payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function applyOverridesToPayload(Tenant $tenant, array $payload): array
    {
        $dictionary = $this->getPhoneticDictionary($tenant);
        $specializedKeywords = $tenant->getSetting('specialized_keywords', []);

        if (empty($dictionary) && empty($specializedKeywords)) {
            return $payload;
        }

        if (! isset($payload['assistantOverrides'])) {
            $payload['assistantOverrides'] = [];
        }

        // Configure transcriber settings if we have terms
        $transcriberProvider = $tenant->getSetting('transcriber_provider', 'deepgram');
        $allKeywords = array_unique(array_merge(array_keys($dictionary), $specializedKeywords));

        $payload['assistantOverrides']['transcriber'] = [
            'provider' => $transcriberProvider,
            'keywords' => array_values($allKeywords),
        ];

        // Format TTS (text-to-speech) phonetic instructions to guide the assistant
        $pronunciationRules = '';
        foreach ($dictionary as $word => $phonetic) {
            $pronunciationRules .= "Always pronounce '{$word}' phonetically as '{$phonetic}'. ";
        }

        if (! empty($pronunciationRules)) {
            if (! isset($payload['assistantOverrides']['variableValues'])) {
                $payload['assistantOverrides']['variableValues'] = [];
            }
            $existingInstructions = $payload['assistantOverrides']['variableValues']['custom_instructions'] ?? '';
            $payload['assistantOverrides']['variableValues']['custom_instructions'] = trim($existingInstructions.' '.$pronunciationRules);
        }

        return $payload;
    }
}
