<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\CallLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('voicemail_fallback')]
#[Title('Voicemail Fallback')]
#[Description('Route active call to voicemail mailbox when technicians are unavailable or busy.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
class VoicemailFallbackTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'call_id' => $schema->string()
                ->description('The call ID of the active call session.')
                ->required(),
            'reason' => $schema->string()
                ->description('Optional reason for voicemail fallback.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'call_id' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        $callId = $validated['call_id'];
        $reason = $validated['reason'] ?? 'No available technicians';

        $callLog = CallLog::where('call_id', $callId)->first();
        if ($callLog) {
            $callLog->update([
                'call_end_reason' => 'forwarded_to_voicemail',
            ]);
        }

        $text = "Routing call to voicemail fallback mailbox due to: {$reason}.";

        return Response::make(Response::text($text))
            ->withStructuredContent([
                'status' => 'forward_to_voicemail',
                'action' => 'transfer',
                'destination' => '+18005550199',
                'message' => $text,
            ]);
    }
}
