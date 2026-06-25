<?php

namespace App\Services;

use App\Jobs\SendWebhookEventJob;
use App\Models\Tenant;

class WebhookNotificationService
{
    /**
     * Dispatch webhook event to all active targets matching event type.
     */
    public function dispatchWebhookEvent(Tenant $tenant, string $eventType, array $payload): void
    {
        $webhooks = $tenant->tenantWebhooks()
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            SendWebhookEventJob::dispatch($webhook, $payload)->onConnection('high-priority');
        }
    }
}
