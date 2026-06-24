<?php

namespace App\Http\Middleware;

use App\Events\WebhookReceived;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebhookIdempotency
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract the unique event identifier
        $eventId = $request->input('event_id')
            ?? $request->input('id')
            ?? $request->header('X-Retell-Signature')
            ?? $request->header('X-Vapi-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('x-vapi-signature')
            ?? $request->header('x-signature');

        if (! $eventId) {
            $eventId = md5($request->getContent());
        }

        // Resolve tenant ID to log/scope statistics
        $tenantIdOrSlug = $request->input('tenant_id')
            ?? $request->input('tenant_slug')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_id')
            ?? $request->input('message.toolCalls.0.function.arguments.tenant_slug')
            ?? $request->route('tenant_id')
            ?? $request->route('tenant_slug');

        $tenantId = null;
        if ($tenantIdOrSlug) {
            $tenant = Cache::remember('tenant-session:'.$tenantIdOrSlug, 600, function () use ($tenantIdOrSlug) {
                return Tenant::where('id', $tenantIdOrSlug)
                    ->orWhere('slug', $tenantIdOrSlug)
                    ->first();
            });
            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        $cacheKey = "idempotency:{$eventId}";

        // 2. Check if this identifier is already cached (successful/completed run)
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            // Sliding TTL of 24 hours
            Cache::put($cacheKey, $cached, now()->addHours(24));

            if ($tenantId) {
                Cache::increment("tenant_duplicate_webhooks:{$tenantId}");

                // Add duplicate event to recent webhook events list in cache
                $eventLog = [
                    'event_id' => $eventId,
                    'event' => (string) ($request->input('event') ?? $request->input('type') ?? 'webhook_hit'),
                    'is_duplicate' => true,
                    'timestamp' => now()->toIso8601String(),
                    'url' => $request->path(),
                ];
                $events = Cache::get("tenant_recent_webhook_events:{$tenantId}", []);
                array_unshift($events, $eventLog);
                $events = array_slice($events, 0, 20);
                Cache::put("tenant_recent_webhook_events:{$tenantId}", $events, 86400);

                // Broadcast duplicate webhook event received
                event(new WebhookReceived(
                    tenantId: $tenantId,
                    eventId: $eventId,
                    event: $eventLog['event'],
                    isDuplicate: true,
                    timestamp: $eventLog['timestamp']
                ));
            }

            Log::info("Idempotency match found for webhook event {$eventId}. Returning cached 200 response.");

            // Re-create the JSON response from cached content and status
            return response()->json($cached['content'] ?? ['success' => true], $cached['status'] ?? 200);
        }

        // 3. Check for dynamic/duplicate processing in progress via atomic lock
        $lockKey = "idempotency_lock:{$eventId}";
        $lock = Cache::lock($lockKey, 15);

        if (! $lock->get()) {
            Log::warning("Idempotency lock active for webhook event {$eventId}. Processing duplicate request as 200 success.");
            if ($tenantId) {
                Cache::increment("tenant_duplicate_webhooks:{$tenantId}");

                // Add duplicate event to list
                $eventLog = [
                    'event_id' => $eventId,
                    'event' => (string) ($request->input('event') ?? $request->input('type') ?? 'webhook_hit'),
                    'is_duplicate' => true,
                    'timestamp' => now()->toIso8601String(),
                    'url' => $request->path(),
                ];
                $events = Cache::get("tenant_recent_webhook_events:{$tenantId}", []);
                array_unshift($events, $eventLog);
                $events = array_slice($events, 0, 20);
                Cache::put("tenant_recent_webhook_events:{$tenantId}", $events, 86400);

                event(new WebhookReceived(
                    tenantId: $tenantId,
                    eventId: $eventId,
                    event: $eventLog['event'],
                    isDuplicate: true,
                    timestamp: $eventLog['timestamp']
                ));
            }

            return response()->json(['success' => true, 'in_progress' => true]);
        }

        try {
            if ($tenantId) {
                Cache::increment("tenant_total_webhooks:{$tenantId}");
            }

            $response = $next($request);

            $statusCode = $response->getStatusCode();
            // Cache only successful/acceptable responses to allow retries on temporary server errors (5xx)
            if ($statusCode < 500) {
                $content = json_decode($response->getContent(), true) ?: $response->getContent();
                Cache::put($cacheKey, [
                    'content' => $content,
                    'status' => $statusCode,
                ], now()->addHours(24));
            }

            if ($tenantId) {
                // Add unique event to list
                $eventLog = [
                    'event_id' => $eventId,
                    'event' => (string) ($request->input('event') ?? $request->input('type') ?? 'webhook_hit'),
                    'is_duplicate' => false,
                    'timestamp' => now()->toIso8601String(),
                    'url' => $request->path(),
                ];
                $events = Cache::get("tenant_recent_webhook_events:{$tenantId}", []);
                array_unshift($events, $eventLog);
                $events = array_slice($events, 0, 20);
                Cache::put("tenant_recent_webhook_events:{$tenantId}", $events, 86400);

                event(new WebhookReceived(
                    tenantId: $tenantId,
                    eventId: $eventId,
                    event: $eventLog['event'],
                    isDuplicate: false,
                    timestamp: $eventLog['timestamp']
                ));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
