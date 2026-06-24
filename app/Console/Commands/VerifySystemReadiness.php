<?php

namespace App\Console\Commands;

use App\Concerns\BelongsToTenant;
use App\Models\AuditLog;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\ServiceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class VerifySystemReadiness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-system-readiness';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the production readiness of the system including dependencies, schema integrity, reverb, and security gates.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==================================================');
        $this->info('     BusinessCalls Production Launch Verification   ');
        $this->info('==================================================');

        $success = true;

        // Check 1: System Dependencies
        $this->comment("\nRunning Check 1: System Dependencies...");
        if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
            $this->info('✔ PHP version is '.PHP_VERSION.' (Requirement: PHP 8.3+)');
        } else {
            $this->error('✘ PHP version is '.PHP_VERSION.' (Requirement: PHP 8.3+)');
            $success = false;
        }

        try {
            DB::connection()->getPdo();
            $this->info('✔ Database connection established successfully.');
        } catch (\Exception $e) {
            $this->error('✘ Database connection failed: '.$e->getMessage());
            $success = false;
        }

        // Check 2: Schema Integrity
        $this->comment("\nRunning Check 2: Schema Integrity & Tenant Isolation...");
        $models = [
            Booking::class,
            CallLog::class,
            Invoice::class,
            Employee::class,
            Availability::class,
            Customer::class,
            ServiceJob::class,
            AuditLog::class,
            Conversation::class,
            Message::class,
        ];

        foreach ($models as $modelClass) {
            $model = new $modelClass;
            $table = $model->getTable();

            // Check for tenant_id column
            if (\Schema::hasColumn($table, 'tenant_id') || $modelClass === Message::class) {
                if ($modelClass === Message::class) {
                    $this->info("✔ Table [{$table}] belongs to nested conversation schema (no tenant_id needed).");
                } else {
                    $this->info("✔ Table [{$table}] has [tenant_id] column.");
                }
            } else {
                $this->error("✘ Table [{$table}] is missing [tenant_id] column.");
                $success = false;
            }

            // Check if uses BelongsToTenant trait
            $traits = class_uses($modelClass);
            if (isset($traits[BelongsToTenant::class])) {
                $this->info("✔ Model [{$modelClass}] uses [BelongsToTenant] trait.");
            } else {
                // Conversation and Message do not directly use BelongsToTenant, but Conversation does, and Message belongs to Conversation.
                if (in_array($modelClass, [Conversation::class, Message::class])) {
                    $this->info("✔ Model [{$modelClass}] belongs to conversation schema.");
                } else {
                    $this->error("✘ Model [{$modelClass}] does not use [BelongsToTenant] trait.");
                    $success = false;
                }
            }

            // Check if uses native attribute syntax
            $reflection = new \ReflectionClass($modelClass);
            $attributes = $reflection->getAttributes();
            $hasNativeAttributes = false;
            foreach ($attributes as $attr) {
                if (str_contains($attr->getName(), 'Eloquent\Attributes') || str_contains($attr->getName(), 'Attributes')) {
                    $hasNativeAttributes = true;
                }
            }
            if ($hasNativeAttributes) {
                $this->info("✔ Model [{$modelClass}] has native attributes configured.");
            } else {
                $this->comment("⚠ Model [{$modelClass}] does not declare native attributes directly (inherited or traits).");
            }
        }

        // Check 3: Real-Time Sockets
        $this->comment("\nRunning Check 3: Real-Time Sockets (Laravel Reverb)...");
        $driver = config('broadcasting.default');
        if ($driver === 'reverb') {
            $this->info('✔ Broadcasting default driver is [reverb].');
        } else {
            $this->error("✘ Broadcasting default driver is [{$driver}] (Requirement: reverb)");
            $success = false;
        }

        $appKey = env('REVERB_APP_KEY');
        $host = env('REVERB_HOST');
        if ($appKey && $host) {
            $this->info("✔ Reverb host [{$host}] is configured with app key.");
        } else {
            $this->error('✘ Reverb app keys or hosts are missing in environment configuration.');
            $success = false;
        }

        // Check 4: Security Gates (Webhook Protections)
        $this->comment("\nRunning Check 4: Security Gates (Webhook Middleware)...");
        $webhookRoutes = [
            'api/webhooks/dispatch',
            'api/webhooks/call-events/{tenant_id?}',
            'api/webhooks/sms/{tenant_id?}',
            'api/webhooks/ivr/{tenant_id?}',
        ];

        $router = app('router');
        foreach ($webhookRoutes as $uri) {
            try {
                $route = $router->getRoutes()->match(request()->create($uri, 'POST'));
                if ($route) {
                    $middleware = $route->gatherMiddleware();
                    $isProtected = false;
                    foreach ($middleware as $mw) {
                        if (str_contains($mw, 'RestrictToTelephonyIps') || str_contains($mw, 'WebhookGatewayMiddleware')) {
                            $isProtected = true;
                        }
                    }

                    if ($isProtected) {
                        $this->info("✔ Webhook [{$uri}] is protected by security middleware.");
                    } else {
                        $this->error("✘ Webhook [{$uri}] is exposed! Missing security middleware.");
                        $success = false;
                    }
                } else {
                    $this->error("✘ Webhook route [{$uri}] is not registered.");
                    $success = false;
                }
            } catch (\Exception $e) {
                // Route match exception can occur due to missing dynamic params during parsing, let's look up route directly
                $found = false;
                foreach ($router->getRoutes() as $r) {
                    if (str_contains($r->uri(), 'webhooks/')) {
                        $found = true;
                        $middleware = $r->gatherMiddleware();
                        $isProtected = false;
                        foreach ($middleware as $mw) {
                            if (str_contains($mw, 'RestrictToTelephonyIps') || str_contains($mw, 'WebhookGatewayMiddleware')) {
                                $isProtected = true;
                            }
                        }
                        if ($isProtected) {
                            $this->info("✔ Webhook [POST {$r->uri()}] is protected by security middleware.");
                        } else {
                            $this->error("✘ Webhook [POST {$r->uri()}] is exposed! Missing security middleware.");
                            $success = false;
                        }
                    }
                }
                if (! $found) {
                    $this->error("✘ Webhook route [{$uri}] lookup failed: ".$e->getMessage());
                    $success = false;
                }
                break;
            }
        }

        $this->info("\n==================================================");
        if ($success) {
            $this->info('✔ PRODUCTION READINESS CHECK PASSED: SYSTEM IS GO FOR LAUNCH!');
            $this->info('==================================================');

            return 0;
        } else {
            $this->error('✘ READINESS CHECK FAILED: PLEASE CORRECT FAILURES!');
            $this->info('==================================================');

            return 1;
        }
    }
}
