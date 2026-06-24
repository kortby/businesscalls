<?php

namespace App\Helpers;

use App\Models\CallLog;

class AgentTransferHelper
{
    /**
     * Format context inheritance payload for child agent.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public static function formatTransferPayload(
        string $childAgentId,
        CallLog $callLog,
        array $variables = []
    ): array {
        return [
            'destination_agent_id' => $childAgentId,
            'context' => [
                'parent_call_id' => $callLog->call_id,
                'parent_transcript_summary' => $callLog->summary ?? '',
                'parent_full_transcript' => $callLog->transcript ?? '',
                'customer_phone' => $callLog->customer_phone,
                'inherited_variables' => $variables,
            ],
            'variables' => array_merge($variables, [
                'customer_phone' => $callLog->customer_phone,
            ]),
            'transfer_history' => [
                'started_at' => $callLog->created_at?->toIso8601String(),
                'parent_duration' => $callLog->duration,
            ],
        ];
    }
}
