<?php

namespace App\AI\Tools;

use Illuminate\Support\Facades\Cache;

class LanguageTransferTool
{
    /**
     * Get tool description for AI function schema.
     */
    public function description(): string
    {
        return 'Hot-swap the active call language and voice model settings (e.g. en, es, fr).';
    }

    /**
     * Get tool parameters schema.
     */
    public function schema(): array
    {
        return [
            'call_id' => [
                'type' => 'string',
                'description' => 'The unique ID of the active call session.',
            ],
            'target_language' => [
                'type' => 'string',
                'description' => 'Target 2-letter ISO code: en, es, fr.',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(string $call_id, string $target_language): array
    {
        $lang = strtolower(trim($target_language));
        if (! in_array($lang, ['en', 'es', 'fr'])) {
            return [
                'status' => 'error',
                'message' => "Unsupported language '{$target_language}'. Supported codes: en, es, fr.",
            ];
        }

        Cache::put("call_language_{$call_id}", $lang, now()->addHours(2));

        return [
            'status' => 'success',
            'call_id' => $call_id,
            'language' => $lang,
            'message' => "Successfully swapped call {$call_id} language to '{$lang}'.",
        ];
    }
}
