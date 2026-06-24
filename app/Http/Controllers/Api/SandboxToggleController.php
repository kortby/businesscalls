<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SandboxToggleController extends Controller
{
    /**
     * Toggle the sandbox/test mode state of the active tenant.
     */
    public function toggle(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return response()->json(['error' => 'Forbidden. Missing tenant context.'], 403);
        }

        $tenant = $user->tenant;
        $tenant->is_test_mode = ! $tenant->is_test_mode;
        $tenant->save();

        // Clear dynamic TenantScope static cache
        TenantScope::setTenantId($tenant->id);

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'sandbox_toggled',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'is_test_mode' => $tenant->is_test_mode,
            ],
        ]);

        return response()->json([
            'success' => true,
            'is_test_mode' => (bool) $tenant->is_test_mode,
            'message' => 'Sandbox mode updated successfully.',
        ]);
    }
}
