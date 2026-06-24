<?php

namespace App\Providers;

use App\Events\CallAnalyzed;
use App\Events\CallEnded;
use App\Jobs\SendFollowUpSmsJob;
use App\Jobs\SyncCallToCrmJob;
use App\Models\Booking;
use App\Models\Tenant;
use App\Observers\BookingObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
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

        // Register zero-downtime replication observers
        Booking::observe(BookingObserver::class);

        // Register toEmbeddings macro
        Str::macro('toEmbeddings', function (string $text) {
            $apiKey = env('OPENAI_API_KEY');
            if ($apiKey && ! app()->runningUnitTests()) {
                try {
                    $response = Http::withToken($apiKey)
                        ->timeout(10)
                        ->post('https://api.openai.com/v1/embeddings', [
                            'model' => 'text-embedding-3-small',
                            'input' => $text,
                        ]);
                    if ($response->successful()) {
                        $embeddings = $response->json('data.0.embedding');
                        if (is_array($embeddings)) {
                            return $embeddings;
                        }
                    }
                } catch (\Exception $e) {
                    // Fall back
                }
            }

            // Deterministic fallback vector of 1536 dimensions
            $len = strlen($text);
            if ($len === 0) {
                return array_fill(0, 1536, 0.0);
            }
            $vector = [];
            $sumOfSquares = 0;
            for ($i = 0; $i < 1536; $i++) {
                $val = sin($len + $i + ord($text[$i % $len] ?? 0));
                $vector[] = $val;
                $sumOfSquares += $val * $val;
            }
            $magnitude = sqrt($sumOfSquares);
            if ($magnitude > 0) {
                foreach ($vector as &$val) {
                    $val = (float) ($val / $magnitude);
                }
            }

            return $vector;
        });

        Stringable::macro('toEmbeddings', function () {
            return Str::toEmbeddings($this->value);
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
