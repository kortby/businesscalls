<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\DraftTask;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CallAnalysisWebhookController extends Controller
{
    /**
     * Handle the incoming call analysis webhook from Vapi or Retell.
     */
    public function handle(Request $request): JsonResponse
    {
        // Extract call ID
        $callId = $request->input('call_id')
            ?? $request->input('call.id')
            ?? $request->input('message.callId')
            ?? $request->input('callId');

        if (! $callId) {
            return response()->json(['error' => 'Missing call_id parameter'], 400);
        }

        // Find or create CallLog
        $callLog = CallLog::where('call_id', $callId)->first();

        $tenantId = $request->input('tenant_id')
            ?? $request->input('message.tenantId')
            ?? $callLog?->tenant_id;

        if (! $tenantId) {
            // Default fallback if we can't find tenant context
            $tenant = Tenant::first();
            $tenantId = $tenant?->id;
        }

        if (! $tenantId) {
            return response()->json(['error' => 'Tenant context not resolved'], 400);
        }

        TenantScope::setTenantId($tenantId);

        $customerPhone = $request->input('customer_phone')
            ?? $request->input('phone')
            ?? $request->input('message.customerPhone')
            ?? $request->input('customerPhone')
            ?? 'Unknown';

        if (! $callLog) {
            $callLog = CallLog::create([
                'tenant_id' => $tenantId,
                'call_id' => $callId,
                'customer_phone' => $customerPhone,
                'status' => 'ended',
            ]);
        }

        // Extract metadata values
        $transcript = $request->input('transcript')
            ?? $request->input('message.transcript')
            ?? $callLog->transcript;

        $summary = $request->input('summary')
            ?? $request->input('message.summary')
            ?? $request->input('analysis.summary')
            ?? $callLog->summary;

        $sentiment = $request->input('user_sentiment')
            ?? $request->input('sentiment')
            ?? $request->input('message.sentiment')
            ?? $request->input('analysis.sentiment');

        $jobCategory = $request->input('job_category')
            ?? $request->input('category')
            ?? $request->input('job_categories')
            ?? $request->input('analysis.category');

        if (is_array($jobCategory)) {
            $jobCategory = implode(', ', $jobCategory);
        }

        $scorecard = $request->input('performance_scorecard')
            ?? $request->input('scorecard')
            ?? $request->input('performance')
            ?? $request->input('analysis.scorecard')
            ?? [];

        // Save metadata on the CallLog
        $callLog->update([
            'transcript' => $transcript,
            'summary' => $summary,
            'user_sentiment' => $sentiment,
            'job_category' => $jobCategory,
            'performance_scorecard' => $scorecard,
            'customer_phone' => $customerPhone,
        ]);

        // Analyze embeddings to detect if follow-up tasks are required
        $textToAnalyze = ($summary ?? '').' '.($transcript ?? '');
        if (! empty(trim($textToAnalyze))) {
            $embedding = Str::of($textToAnalyze)->toEmbeddings();
            $partsTarget = Str::of('order replacement parts')->toEmbeddings();
            $callbackTarget = Str::of('callback to confirm parts delivery')->toEmbeddings();

            $simParts = $this->cosineSimilarity($embedding, $partsTarget);
            $simCallback = $this->cosineSimilarity($embedding, $callbackTarget);

            $bookingId = Cache::get("call_booking_map:{$callId}");

            // Create draft tasks if threshold of 0.85 is reached or keyword fallback matches
            if ($simParts > 0.85 || str_contains(strtolower($textToAnalyze), 'order parts') || str_contains(strtolower($textToAnalyze), 'replacement parts')) {
                DraftTask::create([
                    'tenant_id' => $tenantId,
                    'call_id' => $callId,
                    'booking_id' => $bookingId,
                    'task_type' => 'order_parts',
                    'description' => 'Order replacement parts based on call analysis.',
                    'status' => 'pending',
                ]);
            }

            if ($simCallback > 0.85 || str_contains(strtolower($textToAnalyze), 'call back') || str_contains(strtolower($textToAnalyze), 'confirm parts delivery')) {
                DraftTask::create([
                    'tenant_id' => $tenantId,
                    'call_id' => $callId,
                    'booking_id' => $bookingId,
                    'task_type' => 'callback_customer',
                    'description' => 'Callback to confirm parts delivery with customer.',
                    'status' => 'pending',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'call_id' => $callId,
            'user_sentiment' => $sentiment,
            'job_category' => $jobCategory,
        ]);
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $magA = 0.0;
        $magB = 0.0;
        $count = max(count($vecA), count($vecB));

        for ($i = 0; $i < $count; $i++) {
            $a = $vecA[$i] ?? 0.0;
            $b = $vecB[$i] ?? 0.0;
            $dotProduct += $a * $b;
            $magA += $a * $a;
            $magB += $b * $b;
        }

        return ($magA > 0 && $magB > 0) ? ($dotProduct / (sqrt($magA) * sqrt($magB))) : 0.0;
    }
}
