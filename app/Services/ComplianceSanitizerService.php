<?php

namespace App\Services;

use App\Models\Tenant;

class ComplianceSanitizerService
{
    /**
     * Sanitize input text by masking 16-digit credit cards, SSNs, and sensitive numbers.
     */
    public function sanitize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Mask 16-digit credit card patterns (e.g. 1234-5678-9012-3456 or 1234567890123456)
        $text = preg_replace('/\b\d{4}[- ]?\d{4}[- ]?\d{4}[- ]?\d{4}\b/', '[REDACTED]', $text);

        // Mask standard SSN format (e.g. 123-45-6789)
        $text = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[REDACTED]', $text);

        // Mask raw 9-digit SSN numbers
        $text = preg_replace('/\b\d{9}\b/', '[REDACTED]', $text);

        return $text;
    }

    /**
     * Apply recording opt-outs if GDPR/HIPAA enforcement is enabled.
     */
    public function applyCompliance(Tenant $tenant, array &$payload): void
    {
        if ($tenant->getSetting('gdpr_hipaa_enforcement', false) || $tenant->getSetting('gdpr_hipaa_enabled', false)) {
            $payload['disable_recordings'] = true;
            $payload['recording_enabled'] = false;

            if (isset($payload['assistantOverrides'])) {
                $payload['assistantOverrides']['recordingEnabled'] = false;
                $payload['assistantOverrides']['disable_recordings'] = true;
            }

            if (isset($payload['assistant_overrides'])) {
                $payload['assistant_overrides']['recordingEnabled'] = false;
                $payload['assistant_overrides']['disable_recordings'] = true;
            }
        }
    }
}
