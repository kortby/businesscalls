<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge audit logs older than 180 days across all tenants to optimize database storage.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting audit logs cleanup...');

        // Query without global scope to clean records globally across all tenants
        $deleted = AuditLog::withoutGlobalScopes()
            ->where('created_at', '<', now()->subDays(180))
            ->delete();

        $this->info("Successfully purged {$deleted} audit log records older than 180 days.");

        return 0;
    }
}
