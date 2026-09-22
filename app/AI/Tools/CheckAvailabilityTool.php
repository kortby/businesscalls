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
            $skilledEmployees = $employees->filter(function ($employee) use ($serviceTypeInput) {
                return is_array($employee->skills) && in_array($serviceTypeInput, $employee->skills);
            });

            if ($skilledEmployees->isNotEmpty()) {
                $employees = $skilledEmployees;
            }
        }

        $now = Carbon::now();
        $startDate = Carbon::today();
        $options = [];

        for ($i = 0; $i < 14 && count($options) < 3; $i++) {
            $currentDay = $startDate->copy()->addDays($i);
            $dayOfWeek = $currentDay->dayOfWeek;

            foreach ($employees as $employee) {
                if (count($options) >= 3) {
                    break;
                }

                $shifts = Availability::where('employee_id', $employee->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->get();

                foreach ($shifts as $shift) {
                    if (count($options) >= 3) {
                        break;
                    }

                    $start = Carbon::parse($shift->start_time);
                    $end = Carbon::parse($shift->end_time);

                    $currentHour = $start->copy();
                    while ($currentHour->lt($end) && count($options) < 3) {
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
                                $formattedStr = $slotTime->format('l, M j \a\t g:i A');
                                $isoStr = $slotTime->format('Y-m-d H:i:s');

                                $alreadyAdded = false;
                                foreach ($options as $opt) {
                                    if ($opt['iso'] === $isoStr) {
                                        $alreadyAdded = true;
                                        break;
                                    }
                                }

                                if (! $alreadyAdded) {
                                    $options[] = [
                                        'formatted' => $formattedStr,
                                        'iso' => $isoStr,
                                        'technician_name' => "{$employee->first_name} {$employee->last_name}",
                                        'employee_id' => $employee->id,
                                    ];
                                }
                            }
                        }

                        $currentHour->addHour();
                    }
                }
            }
        }

        $formattedList = array_map(fn ($opt) => $opt['formatted'], $options);

        return [
            'status' => 'success',
            'count' => count($options),
            'options' => $options,
            'formatted_options' => $formattedList,
            'message' => count($options) > 0
                ? 'Available technician options: '.implode(', ', $formattedList)
                : 'No slots available in the next 14 days.',
        ];
    }
}
