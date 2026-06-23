<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AiTestingService;
use Illuminate\Console\Command;

class RunAiSimulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:simulate-batch {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run simulated batch voice logic tests against the telephony API and local webhooks to evaluate agent accuracy.';

    /**
     * Execute the console command.
     */
    public function handle(AiTestingService $aiTestingService): int
    {
        $tenantId = $this->argument('tenant_id');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error('Tenant not found.');

            return 1;
        }

        $this->info("Starting QA Voice Logic simulation for tenant: {$tenant->name} (ID: {$tenant->id})...");

        $results = $aiTestingService->runBatchSimulation($tenant);

        $this->table(
            ['Scenario', 'Phone', 'Trade', 'Time', 'Weight', 'Outcome'],
            array_map(function ($row) {
                return [
                    $row['scenario'],
                    $row['customer_phone'],
                    $row['service_type'],
                    $row['requested_time'],
                    $row['weight'],
                    $row['outcome'] ? '✓ Success' : '✗ Conflict/Error',
                ];
            }, $results['results'])
        );

        $this->info('Simulation run complete.');
        $this->info('Calculated Simulation Accuracy Index (Omega): '.number_format($results['accuracy_index'], 4));
        $this->info('Report logged under tenant settings.');

        return 0;
    }
}
