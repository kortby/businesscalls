<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:cache-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dummy command to satisfy external server scripts invoking permission cache commands';

    /**
     * The command aliases.
     *
     * @var array
     */
    protected $aliases = [
        'permission:cache-clear',
        'permission:clear-cache',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Simulating permission cache reset...');
        $this->info('Permission cache cleared successfully.');

        return Command::SUCCESS;
    }
}
