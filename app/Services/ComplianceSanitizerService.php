<?php

namespace App\Services;

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
}
