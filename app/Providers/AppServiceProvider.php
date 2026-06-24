<?php

namespace App\Providers;

use App\Events\CallAnalyzed;
use App\Events\CallEnded;
use App\Jobs\SendFollowUpSmsJob;
use App\Jobs\SyncCallToCrmJob;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Configure Cashier
        Cashier::useCustomerModel(Tenant::class);
        Cashier::ignoreRoutes();

        // Dispatch CRM sync and SMS follow-up when a call is analyzed
        Event::listen(CallAnalyzed::class, function (CallAnalyzed $event) {
            SyncCallToCrmJob::dispatch($event->callLog);
            SendFollowUpSmsJob::dispatch($event->callLog);
        });

        // Dispatch SMS follow-up when a call ends (handles immediate/fallback triggers)
        Event::listen(CallEnded::class, function (CallEnded $event) {
            SendFollowUpSmsJob::dispatch($event->callLog);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
