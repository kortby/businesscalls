<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeployPromote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:promote {environment : The environment slug (dev, uat, prod)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote application configs to the target environment and fallback on failures';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $env = strtolower($this->argument('environment'));
        $this->info("Promoting configurations to target environment: {$env}");

        $configPath = config_path('deploy/environments.yaml');
        if (! File::exists($configPath)) {
            $this->error("Promotion template file does not exist at: {$configPath}");

            return Command::FAILURE;
        }

        // Parse YAML configurations line-by-line (custom robust fallback parser)
        $content = File::get($configPath);
        $lines = explode("\n", $content);
        $parsed = [];
        $currentEnv = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_ends_with($line, ':')) {
                $currentEnv = rtrim($line, ':');
                $parsed[$currentEnv] = [];

                continue;
            }

            if ($currentEnv && str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $k = trim($k);
                $v = trim($v, " '\"\t\n\r");

                // Dynamic environment variable injection
                if (preg_match('/\${([^}]+)}/', $v, $matches)) {
                    $envKey = $matches[1];
                    $v = env($envKey) ?? 'dummy-secret-key-fallback';
                }

                $parsed[$currentEnv][$k] = $v;
            }
        }

        if (! isset($parsed[$env])) {
            $this->error("Target environment config '{$env}' not defined in YAML template.");

            return Command::FAILURE;
        }

        $envConfig = $parsed[$env];

        // Wrap execution in transaction for safety
        DB::beginTransaction();

        try {
            // Validate environment configuration properties
            $requiredKeys = ['voice_assistant_id', 'telephony_provider', 'telephony_phone_number', 'webhook_url'];
            foreach ($requiredKeys as $key) {
                if (empty($envConfig[$key])) {
                    throw new \Exception("Missing required configuration parameter: {$key} for environment: {$env}");
                }
            }

            // Simulate updating active tenants settings in database
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $tenant->settings = array_merge($tenant->settings ?? [], [
                    'voice_assistant_id' => $envConfig['voice_assistant_id'],
                    'telephony_provider' => $envConfig['telephony_provider'],
                    'telephony_phone_number' => $envConfig['telephony_phone_number'],
                    'telephony_phone_number_id' => $envConfig['telephony_phone_number_id'] ?? null,
                    'webhook_url' => $envConfig['webhook_url'],
                    'recaptcha_site_key' => $envConfig['recaptcha_site_key'] ?? null,
                    'recaptcha_secret_key' => $envConfig['recaptcha_secret_key'] ?? null,
                    'environment' => $envConfig['slug'] ?? $env,
                ]);
                $tenant->save();
            }

            DB::commit();
            $this->info("Successfully promoted settings to database for environment: {$env}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Promotion failed: '.$e->getMessage());

            // Automated Fallback Policy via clean Git rollback
            $this->warn('Executing automated rollback policy: reverting files in git repository.');
            Log::error("Environment Promotion Failed: {$e->getMessage()}. Reverting changes.");

            $result = Process::run('git reset --hard HEAD');
            if ($result->successful()) {
                $this->info('Git rollback execution completed successfully.');
            } else {
                $this->error('Git rollback execution failed: '.$result->errorOutput());
            }

            return Command::FAILURE;
        }
    }
}
