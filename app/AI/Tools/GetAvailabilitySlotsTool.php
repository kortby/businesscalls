<?php

namespace App\AI\Tools;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class GetAvailabilitySlotsTool
{
    public function description(): string
    {
        return 'Retrieve open technician availability slots for a specific date or date range.';
    }

    public function schema(): array
    {
        return [
            'tenant_id' => [
                'type' => 'string',
                'description' => 'The ID or slug of the tenant company.',
            ],
            'date' => [
                'type' => 'string',
                'description' => 'Target date (YYYY-MM-DD or relative like "tomorrow", "next Monday").',
            ],
            'service_type' => [
                'type' => 'string',
                'description' => 'Optional service type or skill required.',
            ],
        ];
    }

    public function handle(string $tenant_id, string $date, ?string $service_type = null): array
    {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant '{$tenant_id}' not found.",
            ];
        }

        try {
            $targetDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "Invalid date format '{$date}'.",
            ];
        }

        $dayOfWeek = $targetDate->dayOfWeek;
        $employees = Employee::where('tenant_id', $tenant->id)->get();

        if ($service_type) {
            $inputNorm = strtolower(trim($service_type));
            $inputClean = rtrim($inputNorm, 's');

            $skilled = $employees->filter(function ($e) use ($inputNorm, $inputClean) {
                if (! is_array($e->skills)) {
                    return false;
                }
                foreach ($e->skills as $skill) {
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

            if ($skilled->isNotEmpty()) {
                $employees = $skilled;
            }
        }

        $slots = [];
        $now = Carbon::now();

        foreach ($employees as $employee) {
            $shifts = Availability::where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->get();

            foreach ($shifts as $shift) {
                $start = Carbon::parse($shift->start_time);
                $end = Carbon::parse($shift->end_time);

                $curr = $start->copy();
                while ($curr->lt($end)) {
                    $slotTime = $targetDate->copy()->setTime($curr->hour, $curr->minute, 0);

                    if ($slotTime->gt($now->copy()->addMinutes(30))) {
                        $startBuf = $slotTime->copy()->subMinutes(90);
                        $endBuf = $slotTime->copy()->addMinutes(90);

                        $hasOverlap = Booking::where('employee_id', $employee->id)
                            ->where('status', 'booked')
                            ->whereBetween('scheduled_start', [$startBuf, $endBuf])
                            ->exists();

                        if (! $hasOverlap) {
                            $slots[] = [
                                'formatted' => $slotTime->format('l, M j \a\t g:i A'),
                                'time' => $slotTime->format('g:i A'),
                                'iso' => $slotTime->toIso8601String(),
                                'technician_name' => "{$employee->first_name} {$employee->last_name}",
                            ];
                        }
                    }

                    $curr->addHour();
                }
            }
        }

        // Sort chronologically by ISO timestamp
        usort($slots, fn ($a, $b) => strcmp($a['iso'], $b['iso']));

        $firstSlot = $slots[0]['time'] ?? null;

        return [
            'status' => 'success',
            'date' => $targetDate->format('Y-m-d'),
            'slots_count' => count($slots),
            'first_available' => $slots[0] ?? null,
            'first_available_time' => $firstSlot,
            'slots' => $slots,
            'message' => count($slots) > 0
                ? 'Found '.count($slots)." open slots for {$targetDate->format('l, M j')}, starting with {$firstSlot}."
                : "No open slots available on {$targetDate->format('l, M j')}.",
        ];
    }
}
