<?php

namespace App\AI\Tools;

use App\Helpers\TradeClassifier;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class GetFirstThreeAvailabilitiesTool
{
    public function description(): string
    {
        return 'Get the first 3 available technician appointment slots formatted for immediate presentation to the customer.';
    }

    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'service_type' => [
                'type' => 'string',
                'description' => 'Optional service type or skill required (e.g. plumbing, HVAC, electrical).',
            ],
        ];
    }

    public function handle(int|string $tenant_id, ?string $service_type = null): array
    {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant with ID or slug '{$tenant_id}' not found.",
            ];
        }

        TenantScope::setTenantId($tenant->id);

        $serviceTypeInput = trim($service_type ?? '');
        $employees = Employee::where('tenant_id', $tenant->id)->get();

        if ($serviceTypeInput !== '') {
            $skilledEmployees = $employees->filter(function ($employee) use ($serviceTypeInput) {
                return TradeClassifier::employeeMatches($employee, $serviceTypeInput);
            });

            if ($skilledEmployees->isNotEmpty()) {
                $employees = $skilledEmployees;
            }
        }

        $now = Carbon::now();
        $startDate = Carbon::today();
        $candidateSlots = [];

        for ($i = 0; $i < 14; $i++) {
            $currentDay = $startDate->copy()->addDays($i);
            $dayOfWeek = $currentDay->dayOfWeek;

            foreach ($employees as $employee) {
                $shifts = Availability::where('employee_id', $employee->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->get();

                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift->start_time);
                    $end = Carbon::parse($shift->end_time);

                    $currentHour = $start->copy();
                    while ($currentHour->lt($end)) {
                        $slotTime = $currentDay->copy()->setTime($currentHour->hour, $currentHour->minute, 0);

                        if ($slotTime->gt($now->copy()->addMinutes(30))) {
                            $bufferMinutes = 90;
                            $startBuffer = $slotTime->copy()->subMinutes($bufferMinutes);
                            $endBuffer = $slotTime->copy()->addMinutes($bufferMinutes);

                            $hasOverlap = Booking::where('employee_id', $employee->id)
                                ->where('status', 'booked')
                                ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                                ->exists();

                            if (! $hasOverlap) {
                                $candidateSlots[] = [
                                    'timestamp' => $slotTime->timestamp,
                                    'formatted' => $slotTime->format('l, M j \a\t g:i A'),
                                    'iso' => $slotTime->format('Y-m-d H:i:s'),
                                    'technician_name' => "{$employee->first_name} {$employee->last_name}",
                                    'employee_id' => $employee->id,
                                ];
                            }
                        }

                        $currentHour->addHour();
                    }
                }
            }
        }

        // Sort candidates chronologically so the absolute earliest slot is first
        usort($candidateSlots, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        $options = [];
        $seenTimes = [];
        foreach ($candidateSlots as $slot) {
            if (! isset($seenTimes[$slot['iso']])) {
                $seenTimes[$slot['iso']] = true;
                $options[] = [
                    'formatted' => $slot['formatted'],
                    'iso' => $slot['iso'],
                    'technician_name' => $slot['technician_name'],
                    'employee_id' => $slot['employee_id'],
                ];
                if (count($options) >= 3) {
                    break;
                }
            }
        }

        $formattedList = array_map(fn ($opt) => $opt['formatted'], $options);
        $firstAvailable = $options[0]['formatted'] ?? null;

        return [
            'status' => 'success',
            'count' => count($options),
            'first_available' => $options[0] ?? null,
            'first_available_formatted' => $firstAvailable,
            'options' => $options,
            'formatted_options' => $formattedList,
            'message' => count($options) > 0
                ? "Our first available appointment is {$firstAvailable}. The first ".count($options).' available technician options are: '.implode(', ', $formattedList).". If that doesn't work, what day and time would you prefer?"
                : 'No available technician slots found over the next 14 days.',
        ];
    }
}
