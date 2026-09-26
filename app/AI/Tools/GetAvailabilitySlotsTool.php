<?php

namespace App\AI\Tools;

use App\Helpers\TradeClassifier;
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
            $serviceTypeInput = trim($service_type);
            $skilled = $employees->filter(function ($e) use ($serviceTypeInput) {
                return TradeClassifier::employeeMatches($e, $serviceTypeInput);
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
                                'employee_id' => $employee->id,
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
