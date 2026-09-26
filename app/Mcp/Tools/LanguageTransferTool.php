<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AI\Tools\LanguageTransferTool as BackendLanguageTransferTool;
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

#[Name('language_transfer')]
#[Title('Language Hot-Swap Transfer')]
#[Description('Hot-swap active call language and speech model settings (e.g. en, es, fr).')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
class LanguageTransferTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'call_id' => $schema->string()
                ->description('The unique ID of the active call session.')
                ->required(),
            'target_language' => $schema->string()
                ->description('Target 2-letter ISO language code: en, es, fr.')
                ->enum(['en', 'es', 'fr'])
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'call_id' => ['required', 'string'],
            'target_language' => ['required', 'string', 'in:en,es,fr'],
        ]);

        $backend = new BackendLanguageTransferTool;
        $result = $backend->handle((string) $validated['call_id'], (string) $validated['target_language']);

        if (($result['status'] ?? '') === 'error') {
            return Response::error($result['message'] ?? 'Failed to switch call language.');
        }

        return Response::make(Response::text($result['message'] ?? 'Call language transferred.'))
            ->withStructuredContent($result);
    }
}
