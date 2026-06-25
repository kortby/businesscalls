<?php

namespace App\Services;

use App\Jobs\TriggerWorkflowIntegrationJob;
use App\Models\CallLog;
use App\Models\Tenant;
use App\Models\TenantIntegration;

class WorkflowIntegrationService
{
    /**
     * Trigger external workflow integrations for a given tenant.
     */
    public function triggerExternalWorkflow(Tenant $tenant, string $eventName, array $payload): void
    {
        $callId = request()->input('call_id')
            ?? request()->input('call.id')
            ?? request()->input('message.call.id')
            ?? request()->input('message.callId')
            ?? request()->input('message.call.callId');

        if ($callId) {
            $callLog = CallLog::where('call_id', $callId)->first();
            if ($callLog) {
                $callLog->increment('integrations_count');
            }
        }

        $integrations = TenantIntegration::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        foreach ($integrations as $integration) {
            $webhookUrl = $integration->webhook_url;

            if (empty($webhookUrl)) {
                continue;
            }

            $payloadData = array_merge([
                'event_name' => $eventName,
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'platform_name' => $integration->platform_name,
            ], $payload);

            TriggerWorkflowIntegrationJob::dispatch($webhookUrl, $payloadData);
        }
    }
}
