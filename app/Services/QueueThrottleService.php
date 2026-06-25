<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class QueueThrottleService
{
    /**
     * Check if ongoing concurrent calls are approaching the tenant limit,
     * and throttle execution using exponential backoff with randomized jitter.
     */
    public function throttleIfCongested(Tenant $tenant): void
    {
        $attempt = 0;

        while (true) {
            // Isolated multi-tenant query automatically scoped by TenantScope
            $activeCount = CallLog::where('status', 'ongoing')->count();
            $limit = (int) $tenant->getSetting('concurrent_call_limit', 20);

            $threshold = 0.9 * $limit;

            if ($activeCount < $threshold) {
                break;
            }

            $attempt++;
            // Calculate exponential backoff delay in milliseconds, capped at min(attempt, 6)
            $backoffMs = (int) (pow(2, min($attempt, 6)) * 1000 + rand(0, 1000));

            Log::warning("Campaign Queue Congested for Tenant {$tenant->id} ({$tenant->name}): {$activeCount}/{$limit} active call channels. Throttling for {$backoffMs}ms.");

            if (app()->runningUnitTests()) {
                usleep(10); // Prevent delay in test environments
            } else {
                usleep($backoffMs * 1000);
            }

            // Safe exit to prevent infinite loop on stale DB sessions
            if ($attempt >= 8) {
                Log::error('Campaign Queue Congestion exceeded maximum backoff retries. Continuing.');
                break;
            }
        }
    }
}
