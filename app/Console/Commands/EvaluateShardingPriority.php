<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:evaluate-sharding-priority {--w1=0.4 : Weight for records count} {--w2=0.3 : Weight for concurrent channels} {--w3=0.3 : Weight for query latency}')]
#[Description('Evaluate the sharding priority index for each tenant to determine migration thresholds')]
class EvaluateShardingPriority extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $w1 = (float) $this->option('w1');
        $w2 = (float) $this->option('w2');
        $w3 = (float) $this->option('w3');

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');

            return 0;
        }

        $this->info("Evaluating sharding priority for {$tenants->count()} tenants...");
        $this->info("Weights: w1={$w1}, w2={$w2}, w3={$w3}");

        $headers = ['Tenant ID', 'Tenant Name', 'Records Count', 'Peak Channels', 'Avg Query Delay (ms)', 'Priority Index (Phi)'];
        $rows = [];

        foreach ($tenants as $tenant) {
            // V_records: CallLog, Booking, Conversation counts
            $vRecords = CallLog::where('tenant_id', $tenant->id)->count()
                + Booking::where('tenant_id', $tenant->id)->count()
                + Conversation::where('tenant_id', $tenant->id)->count();

            // C_concurrent: Peak concurrent active WebRTC/SIP channels this month from settings
            $cConcurrent = (int) $tenant->getSetting('peak_concurrent_channels', 0);

            // tau_query: Running average database query response delay in ms
            $tauQuery = (float) $tenant->getSetting('avg_query_delay_ms', 5.0);

            // Calculate Phi_shard = w1 * (V_records / 10^6) + w2 * (C_concurrent / 50) + w3 * tau_query
            $phiShard = ($w1 * ($vRecords / 1000000.0))
                + ($w2 * ($cConcurrent / 50.0))
                + ($w3 * $tauQuery);

            // Save the priority index to settings
            $settings = $tenant->settings ?? [];
            $settings['sharding_priority_index'] = $phiShard;
            $settings['records_count'] = $vRecords;
            $tenant->settings = $settings;
            $tenant->save();

            $rows[] = [
                $tenant->id,
                $tenant->name,
                $vRecords,
                $cConcurrent,
                $tauQuery,
                number_format($phiShard, 4),
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }
}
