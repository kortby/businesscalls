<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CallLog;
use App\Services\ComplianceSanitizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallRedactionController extends Controller
{
    /**
     * Manually redact sensitive data in the specified call log transcript.
     */
    public function redact(Request $request, CallLog $callLog): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isSupervisor()) {
            return response()->json(['error' => 'Forbidden. Supervisor override required.'], 403);
        }

        // Additional tenant isolation assertion
        if ($callLog->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Forbidden. Tenant mismatch.'], 403);
        }

        $sanitizer = app(ComplianceSanitizerService::class);

        $originalTranscript = $callLog->transcript;
        $originalSummary = $callLog->summary;

        $callLog->transcript = $sanitizer->sanitize($callLog->transcript);
        $callLog->summary = $sanitizer->sanitize($callLog->summary);
        $callLog->save();

        // Write manual redaction event under isolated audit logs
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'manual_redaction',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'call_log_id' => $callLog->id,
                'call_id' => $callLog->call_id,
                'redacted_fields' => [
                    'transcript' => $originalTranscript !== $callLog->transcript,
                    'summary' => $originalSummary !== $callLog->summary,
                ],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call log redact completed successfully.',
            'call_log' => $callLog,
        ]);
    }
}
