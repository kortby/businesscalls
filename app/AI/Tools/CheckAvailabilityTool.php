<?php

namespace App\AI\Tools;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class CheckAvailabilityTool
{
    /**
     * Get tool description for AI function schema.
     */
    public function description(): string
    {
        return 'Check the first available technician appointment slots for a tenant based on service type over the next 14 days.';
    }

    /**
     * Get tool parameters schema.
     */
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

    /**
     * Execute the tool.
     */
    public function handle(string $tenant_id, ?string $service_type = null): array
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

        $serviceTypeInput = trim($service_type ?? '');
        $employees = Employee::where('tenant_id', $tenant->id)->get();

        if ($serviceTypeInput !== '') {
            $inputNorm = strtolower($serviceTypeInput);
            $inputClean = rtrim($inputNorm, 's');

            $skilledEmployees = $employees->filter(function ($employee) use ($inputNorm, $inputClean) {
                if (! is_array($employee->skills)) {
                    return false;
                }
                foreach ($employee->skills as $skill) {
                    $s = strtolower(trim((string) $skill));
                    if ($s === $inputNorm || $s === $inputClean
                        || str_contains($s, $inputNorm) || str_contains($inputNorm, $s)
                        || ((str_contains($inputNorm, 'plumb') || str_contains($inputNorm, 'plumm') || str_contains($inputNorm, 'drain') || str_contains($inputNorm, 'sewer') || str_contains($inputNorm, 'pipe') || str_contains($inputNorm, 'water heater') || str_contains($inputNorm, 'toilet') || str_contains($inputNorm, 'faucet') || str_contains($inputNorm, 'leak') || str_contains($inputNorm, 'clog') || str_contains($inputNorm, 'rooter') || str_contains($inputNorm, 'sink'))
                            && (str_contains($s, 'plumb') || str_contains($s, 'drain') || str_contains($s, 'sewer') || str_contains($s, 'pipe') || str_contains($s, 'water-heaters') || str_contains($s, 'leak') || str_contains($s, 'toilet') || str_contains($s, 'faucet')))
                        || ((str_contains($inputNorm, 'electr') || str_contains($inputNorm, 'power') || str_contains($inputNorm, 'breaker') || str_contains($inputNorm, 'outlet') || str_contains($inputNorm, 'wiring') || str_contains($inputNorm, 'lighting') || str_contains($inputNorm, 'panel') || str_contains($inputNorm, 'wire'))
                            && (str_contains($s, 'electr') || str_contains($s, 'breaker') || str_contains($s, 'wiring') || str_contains($s, 'voltage') || str_contains($s, 'lighting')))
                        || ((str_contains($inputNorm, 'hvac') || str_contains($inputNorm, 'ac') || str_contains($inputNorm, 'heat') || str_contains($inputNorm, 'air') || str_contains($inputNorm, 'cool') || str_contains($inputNorm, 'furnace') || str_contains($inputNorm, 'duct') || str_contains($inputNorm, 'thermostat') || str_contains($inputNorm, 'ventilation'))
                            && (str_contains($s, 'hvac') || str_contains($s, 'heat') || str_contains($s, 'ac') || str_contains($s, 'air') || str_contains($s, 'cool') || str_contains($s, 'furnace') || str_contains($s, 'duct') || str_contains($s, 'ventilation')))
                        || ((str_contains($inputNorm, 'appliance') || str_contains($inputNorm, 'fridge') || str_contains($inputNorm, 'refrigerator') || str_contains($inputNorm, 'washer') || str_contains($inputNorm, 'dryer') || str_contains($inputNorm, 'stove') || str_contains($inputNorm, 'oven') || str_contains($inputNorm, 'dishwasher'))
                            && (str_contains($s, 'appliance') || str_contains($s, 'refrigerator') || str_contains($s, 'dryer') || str_contains($s, 'stove') || str_contains($s, 'dishwasher') || str_contains($s, 'washer') || str_contains($s, 'oven')))
                        || ((str_contains($inputNorm, 'roof') || str_contains($inputNorm, 'gutter') || str_contains($inputNorm, 'siding') || str_contains($inputNorm, 'shingle'))
                            && (str_contains($s, 'roof') || str_contains($s, 'gutter') || str_contains($s, 'siding')))
                        || ((str_contains($inputNorm, 'lock') || str_contains($inputNorm, 'key') || str_contains($inputNorm, 'deadbolt') || str_contains($inputNorm, 'rekey'))
                            && (str_contains($s, 'lock') || str_contains($s, 'rekey') || str_contains($s, 'key')))
                    ) {
                        return true;
                    }
                }

                return false;
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

        // Sort chronologically
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
                ? "Our first available appointment is {$firstAvailable}. Available technician options: ".implode(', ', $formattedList).'. If that time does not work, what day and time would you prefer?'
                : 'No slots available in the next 14 days.',
        ];
    }
}
