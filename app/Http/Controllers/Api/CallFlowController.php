<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallFlowController extends Controller
{
    /**
     * Store/update the tenant's visual call flow tree.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $tenant = Tenant::find($user->tenant_id);
        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        $validated = $request->validate([
            'call_flow_tree' => 'required|array',
        ]);

        $settings = $tenant->settings ?? [];
        $settings['call_flow_tree'] = $validated['call_flow_tree'];
        $tenant->settings = $settings;
        $tenant->save();

        return response()->json(['success' => true]);
    }
}
