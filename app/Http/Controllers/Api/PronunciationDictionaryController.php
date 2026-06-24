<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PronunciationDictionaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PronunciationDictionaryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PronunciationDictionaryService $dictionaryService
    ) {}

    /**
     * Register a new phonetic dictionary entry for the tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'Unauthorized or missing active tenant.'], 403);
        }

        $validated = $request->validate([
            'word' => ['required', 'string', 'max:255'],
            'phonetic' => ['required', 'string', 'max:255'],
        ]);

        $this->dictionaryService->registerPhoneticSpelling($tenant, $validated['word'], $validated['phonetic']);

        return response()->json([
            'success' => true,
            'message' => 'Phonetic pronunciation dictionary updated successfully.',
            'dictionary' => $this->dictionaryService->getPhoneticDictionary($tenant),
        ]);
    }
}
