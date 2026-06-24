<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecializedKeywordsController extends Controller
{
    /**
     * Get the registered specialized trade keywords for the active tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'Unauthorized or missing active tenant.'], 403);
        }

        $keywords = $tenant->getSetting('specialized_keywords', []);

        return response()->json([
            'success' => true,
            'keywords' => $keywords,
        ]);
    }

    /**
     * Register specialized trade keywords for the active tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'Unauthorized or missing active tenant.'], 403);
        }

        $validated = $request->validate([
            'keywords' => ['required', 'array'],
            'keywords.*' => ['required', 'string', 'max:255'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['specialized_keywords'] = array_map('trim', $validated['keywords']);
        $tenant->settings = $settings;
        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Specialized trade keywords updated successfully.',
            'keywords' => $tenant->getSetting('specialized_keywords', []),
        ]);
    }
}
