<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTestingService
{
    /**
     * Run a batch of QA Voice Simulations.
     *
     * @param  array|null  $scenarios  Custom scenarios to run. If null, uses defaults.
     * @return array{accuracy_index: float, total_cases: int, results: array}
     */
    public function runBatchSimulation(Tenant $tenant, ?array $scenarios = null): array
    {
        $scenarios ??= $this->getDefaultScenarios();
        $results = [];
        $totalWeight = 0;
        $weightedSuccess = 0;
        $apiKey = env('TELEPHONY_API_KEY', 'dummy-telephony-api-key');
        $provider = config('services.telephony.provider', env('TELEPHONY_PROVIDER', 'vapi'));

        // Apply tenant scope context for safety
        TenantScope::setTenantId($tenant->id);

        // 1. Submit scenarios programmatically in bulk to telephony provider's Batch Testing / Eval API
        try {
            if ($provider === 'vapi') {
                $response = Http::withToken($apiKey)
                    ->post('https://api.vapi.ai/eval/run', [
                        'tenant_id' => $tenant->id,
                        'scenarios' => $scenarios,
                    ]);
            } else {
                $response = Http::withToken($apiKey)
                    ->post('https://api.retellai.com/evals', [
                        'tenant_id' => $tenant->id,
                        'scenarios' => $scenarios,
                    ]);
            }
        } catch (\Exception $e) {
            Log::warning('Telephony eval API request failed: '.$e->getMessage().'. Running in simulated fallback mode.');
            $response = Http::response(['status' => 'simulated'], 200);
        }

        // 2. Iterate through each test case scenario
        foreach ($scenarios as $index => $scenario) {
            $customerPhone = $scenario['customer_phone'];
            $serviceType = $scenario['service_type'];
            $requestedTime = $scenario['requested_time'];
            $weight = $scenario['weight'] ?? 1;

            // Delete any existing booking for this test customer to ensure clean outcome check
            Booking::where('customer_phone', $customerPhone)
                ->where('tenant_id', $tenant->id)
                ->delete();

            // Simulate the webhook scheduling logic locally (outcome)
            $success = $this->simulateWebhookScheduling($tenant, $customerPhone, $serviceType, $requestedTime);

            $mu = $success ? 1 : 0;
            $weightedSuccess += ($mu * $weight);
            $totalWeight += $weight;

            $results[] = [
                'scenario' => $scenario['name'] ?? 'Test Case '.($index + 1),
                'customer_phone' => $customerPhone,
                'service_type' => $serviceType,
                'requested_time' => $requestedTime,
                'weight' => $weight,
                'outcome' => $mu,
            ];
        }

        // Calculate Simulation Accuracy Index: Omega = sum(mu_i * w_i) / N
        $N = count($scenarios);
        $omega = $N > 0 ? (float) ($weightedSuccess / $N) : 0.0;

        // 3. Log these metrics under parent tenant configuration settings
        $simulations = $tenant->getSetting('qa_simulations', []);
        $simulations[] = [
            'timestamp' => now()->toIso8601String(),
            'accuracy_index' => $omega,
            'total_cases' => $N,
            'results' => $results,
        ];

        $settings = $tenant->settings ?? [];
        $settings['qa_simulations'] = $simulations;
        $tenant->settings = $settings;
        $tenant->save();

        return [
            'accuracy_index' => $omega,
            'total_cases' => $N,
            'results' => $results,
        ];
    }

    /**
     * Check if a mock call would successfully schedule a booking.
     */
    protected function simulateWebhookScheduling(Tenant $tenant, string $customerPhone, string $serviceType, string $requestedTime): bool
    {
        TenantScope::setTenantId($tenant->id);

        try {
            $requestedTimeCarbon = Carbon::parse($requestedTime);
        } catch (\Exception $e) {
            return false;
        }

        $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
        $timeOnly = $requestedTimeCarbon->format('H:i:s');

        // Match employees with skill and shift availability, and check collisions
        $employees = Employee::get()->filter(function ($employee) use ($serviceType) {
            return is_array($employee->skills) && in_array($serviceType, $employee->skills);
        });

        foreach ($employees as $employee) {
            $isAvailable = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->where('start_time', '<=', $timeOnly)
                ->where('end_time', '>=', $timeOnly)
                ->exists();

            if (! $isAvailable) {
                continue;
            }

            $startBuffer = $requestedTimeCarbon->copy()->subMinutes(90);
            $endBuffer = $requestedTimeCarbon->copy()->addMinutes(90);

            $hasOverlap = Booking::where('employee_id', $employee->id)
                ->where('status', 'booked')
                ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                ->exists();

            if (! $hasOverlap) {
                // If it can be scheduled, simulate the database transaction
                Booking::create([
                    'tenant_id' => $tenant->id,
                    'employee_id' => $employee->id,
                    'customer_phone' => $customerPhone,
                    'job_details' => "Simulated QA dispatch for {$serviceType}",
                    'status' => 'booked',
                    'scheduled_start' => $requestedTimeCarbon,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Get default customer booking scenarios.
     */
    public function getDefaultScenarios(): array
    {
        $today = now()->startOfWeek();

        return [
            [
                'name' => 'Urgent HVAC furnace breakdown (Emergency)',
                'customer_phone' => '+15551000001',
                'service_type' => 'hvac',
                'requested_time' => $today->copy()->addDays(1)->setTime(16, 0, 0)->toIso8601String(), // Tuesday 4 PM
                'weight' => 3, // Emergency
            ],
            [
                'name' => 'Non-emergency faucet replacement',
                'customer_phone' => '+15551000002',
                'service_type' => 'plumbing',
                'requested_time' => $today->copy()->addDays(2)->setTime(9, 0, 0)->toIso8601String(), // Wednesday 9 AM
                'weight' => 1,
            ],
            [
                'name' => 'Routine AC diagnostic',
                'customer_phone' => '+15551000003',
                'service_type' => 'hvac',
                'requested_time' => $today->copy()->addDays(3)->setTime(14, 0, 0)->toIso8601String(), // Thursday 2 PM
                'weight' => 2,
            ],
            [
                'name' => 'Emergency leaky pipe in basement',
                'customer_phone' => '+15551000004',
                'service_type' => 'plumbing',
                'requested_time' => $today->copy()->addDays(4)->setTime(11, 0, 0)->toIso8601String(), // Friday 11 AM
                'weight' => 3,
            ],
        ];
    }
}
