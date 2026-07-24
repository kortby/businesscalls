<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityWebhookController extends Controller
{
    /**
     * Handle incoming AI request for the first 3 availability options for booking a technician.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Parse parameters (supporting flat and nested Vapi payload structures)
        $toolCallId = $request->input('message.toolCalls.0.id');
        $arguments = $request->input('message.toolCalls.0.function.arguments', []);

        $tenantIdOrSlug = $arguments['tenant_id']
            ?? $request->input('tenant_id')
            ?? $arguments['tenant_slug']
            ?? $request->input('tenant_slug')
            ?? $request->input('message.tenantId')
            ?? $request->route('tenant_id');

        if (! $tenantIdOrSlug) {
            $dialedNumber = $request->input('message.phoneNumber.number')
                ?? $request->input('message.phone.number')
                ?? $request->input('phoneNumber');

            if ($dialedNumber) {
                $tenant = Tenant::where('settings->telephony_phone_number', $dialedNumber)->first();
                if ($tenant) {
                    $tenantIdOrSlug = $tenant->id;
                }
            }
        }

        if (! $tenantIdOrSlug) {
            return response()->json([
                'error' => 'Missing required field: tenant_id or tenant_slug must be provided.',
            ], 400);
        }

        // 2. Resolve Tenant
        $tenant = Tenant::where('id', $tenantIdOrSlug)
            ->orWhere('slug', $tenantIdOrSlug)
            ->first();

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found.',
            ], 404);
        }

        TenantScope::setTenantId($tenant->id);

        $serviceTypeInput = $arguments['service_type']
            ?? $arguments['serviceType']
            ?? $request->input('service_type')
            ?? $request->input('serviceType')
            ?? '';

        if (! $serviceTypeInput) {
            return response()->json([
                'error' => 'Missing required field: service_type must be provided.',
            ], 400);
        }

        // 3. Find active technicians matching skill
        $employees = Employee::where('tenant_id', $tenant->id)->get()->filter(function ($employee) use ($serviceTypeInput) {
            return is_array($employee->skills) && in_array($serviceTypeInput, $employee->skills);
        });

        if ($employees->isEmpty()) {
            // Fallback to all employees for tenant if no specific skill match
            $employees = Employee::where('tenant_id', $tenant->id)->get();
        }

        $now = Carbon::now();
        $startDate = Carbon::today();
        $options = [];

        // 4. Scan next 14 days for the first 3 available slots
        for ($i = 0; $i < 14 && count($options) < 3; $i++) {
            $currentDay = $startDate->copy()->addDays($i);
            $dayOfWeek = $currentDay->dayOfWeek; // 0 (Sun) to 6 (Sat)

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

                        // Don't offer past times
                        if ($slotTime->gt($now->copy()->addMinutes(30))) {
                            // Check travel buffer conflict (90-minute window)
                            $bufferMinutes = 90;
                            $startBuffer = $slotTime->copy()->subMinutes($bufferMinutes);
                            $endBuffer = $slotTime->copy()->addMinutes($bufferMinutes);

                            $hasOverlap = Booking::where('employee_id', $employee->id)
                                ->where('status', 'booked')
                                ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
                                ->exists();

                            if (! $hasOverlap) {
                                // Double check slot isn't already added
                                $formattedStr = $slotTime->format('l, M j \a\t g:i A'); // e.g. "Monday, Jul 27 at 9:00 AM"
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
        $messageStr = count($options) > 0
            ? 'The first '.count($options).' available technician options are: '.implode(', ', $formattedList).'.'
            : 'No available technician slots were found for the requested service over the next 14 days.';

        $resultData = [
            'status' => 'success',
            'count' => count($options),
            'options' => $options,
            'formatted_options' => $formattedList,
            'message' => $messageStr,
        ];

        if ($toolCallId) {
            return response()->json([
                'results' => [
                    [
                        'toolCallId' => $toolCallId,
                        'result' => $resultData,
                    ],
                ],
            ]);
        }

        return response()->json($resultData);
    }
}
