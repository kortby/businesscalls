<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteBatchCampaignJob;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CampaignRecipient;
use App\Models\MaintenanceAgreement;
use App\Models\OutboundCampaign;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessMaintenanceAgreements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-maintenance-agreements {--tenant= : Scope to a specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active maintenance agreements and launch preventative maintenance outbound campaigns if capacity is available.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        foreach ($tenants as $tenant) {
            // Apply multi-tenant isolation context
            TenantScope::setTenantId($tenant->id);

            // 1. Capacity-Aware Safety Gate Check
            if (! $this->hasWeeklyCapacity($tenant)) {
                $this->warn("Tenant {$tenant->name} is at or over capacity for the active week. Skipping proactive PM scheduler.");
                Log::info("PM Scheduler: Tenant {$tenant->name} skipped due to capacity limits.");

                continue;
            }

            // 2. Identify due agreements
            // Due if next_service_due is in the past/today, or last_service_date is 11+ months ago
            $cutoffDate = Carbon::today()->subMonths(11);

            $dueAgreements = MaintenanceAgreement::where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->where(function ($query) use ($cutoffDate) {
                    $query->where('next_service_due', '<=', Carbon::today())
                        ->orWhere('last_service_date', '<=', $cutoffDate);
                })
                ->with('customer')
                ->get();

            if ($dueAgreements->isEmpty()) {
                $this->info("No due maintenance agreements found for tenant {$tenant->name}.");

                continue;
            }

            $this->info("Found {$dueAgreements->count()} due agreements for tenant {$tenant->name}. Building outbound campaign...");

            // 3. Create Outbound Campaign
            $campaign = OutboundCampaign::create([
                'tenant_id' => $tenant->id,
                'status' => 'draft',
                'target_group' => 'Proactive Preventative Maintenance',
                'schedule_time' => now(),
            ]);

            // 4. Create Campaign Recipients
            foreach ($dueAgreements as $agreement) {
                $customer = $agreement->customer;
                if (! $customer) {
                    continue;
                }

                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'phone_number' => $customer->phone,
                    'name' => $customer->name,
                    'status' => 'pending',
                ]);
            }

            // 5. Dispatch job to execute campaign call batch
            ExecuteBatchCampaignJob::dispatch($campaign);

            $this->info("Campaign {$campaign->id} dispatched successfully with {$campaign->recipients()->count()} recipients.");
            Log::info("PM Scheduler: Launched campaign {$campaign->id} for tenant {$tenant->name}.");
        }

        // Reset tenant scope
        TenantScope::setTenantId(null);
    }

    /**
     * Check if the active week has open capacity slots on the dispatch board.
     */
    protected function hasWeeklyCapacity(Tenant $tenant): bool
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Count existing bookings for this tenant this week
        $activeBookingsCount = Booking::where('tenant_id', $tenant->id)
            ->where('status', 'booked')
            ->whereBetween('scheduled_start', [$startOfWeek, $endOfWeek])
            ->count();

        // Get total availability shifts for this tenant
        $totalAvailableShifts = Availability::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($totalAvailableShifts === 0) {
            return false;
        }

        // Capacity threshold is 2 bookings per shift on average
        return $activeBookingsCount < ($totalAvailableShifts * 2);
    }
}
