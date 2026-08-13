<?php

namespace App\AI\Tools;

use App\Events\DispatchUpdated;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class CreateBookingTool
{
    /**
     * Get tool description for AI function schema.
     */
    public function description(): string
    {
        return 'Create a new appointment booking for a customer with a specific technician or slot.';
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
            'customer_phone' => [
                'type' => 'string',
                'description' => 'Customer phone number.',
            ],
            'job_details' => [
                'type' => 'string',
                'description' => 'Brief description of the service job requested.',
            ],
            'scheduled_start' => [
                'type' => 'string',
                'description' => 'Target start date/time (ISO 8601 or Y-m-d H:i:s).',
            ],
            'employee_id' => [
                'type' => 'integer',
                'description' => 'Optional technician ID to assign.',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(
        string $tenant_id,
        string $customer_phone,
        string $job_details,
        string $scheduled_start,
        ?int $employee_id = null
    ): array {
        $tenant = Tenant::where('id', $tenant_id)
            ->orWhere('slug', $tenant_id)
            ->first();

        if (! $tenant) {
            return [
                'status' => 'error',
                'message' => "Tenant with ID or slug '{$tenant_id}' not found.",
            ];
        }

        try {
            $requestedTimeCarbon = Carbon::parse($scheduled_start);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Invalid scheduled_start date format.',
            ];
        }

        $assignedEmployeeId = $employee_id;

        // Auto-assign first available technician if employee_id is not specified
        if (! $assignedEmployeeId) {
            $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
            $timeOnly = $requestedTimeCarbon->format('H:i:s');

            $availableTechnician = Employee::where('tenant_id', $tenant->id)
                ->whereHas('availabilities', function ($q) use ($dayOfWeek, $timeOnly) {
                    $q->where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->where('start_time', '<=', $timeOnly)
                        ->where('end_time', '>=', $timeOnly);
                })
                ->first();

            if (! $availableTechnician) {
                // Fallback to any active tenant technician
                $availableTechnician = Employee::where('tenant_id', $tenant->id)->first();
            }

            if (! $availableTechnician) {
                return [
                    'status' => 'error',
                    'message' => 'No active technicians found for this tenant.',
                ];
            }

            $assignedEmployeeId = $availableTechnician->id;
        }

        $employee = Employee::find($assignedEmployeeId);

        // Verify shift availability
        $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
        $timeOnly = $requestedTimeCarbon->format('H:i:s');
        $isAvailable = Availability::where('employee_id', $assignedEmployeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $timeOnly)
            ->where('end_time', '>=', $timeOnly)
            ->exists();

        if (! $isAvailable) {
            return [
                'status' => 'error',
                'message' => "Technician {$employee->first_name} is not scheduled during {$requestedTimeCarbon->format('l g:i A')}.",
            ];
        }

        // Verify travel buffer
        $bufferMinutes = 90;
        $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
        $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

        $hasOverlap = Booking::where('employee_id', $assignedEmployeeId)
            ->where('status', 'booked')
            ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
            ->exists();

        if ($hasOverlap) {
            return [
                'status' => 'error',
                'message' => 'Requested slot conflicts with an existing technician appointment (1.5-hour travel buffer enforced).',
            ];
        }

        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $assignedEmployeeId,
            'customer_phone' => $customer_phone,
            'job_details' => $job_details,
            'status' => 'booked',
            'scheduled_start' => $requestedTimeCarbon,
        ]);

        $booking->assignTaskToTechnician();

        event(new DispatchUpdated($tenant->id, [
            'type' => 'success',
            'message' => "AI Voice Booking confirmed for {$customer_phone} at {$requestedTimeCarbon->format('Y-m-d H:i')}.",
            'booking' => $booking->load('employee'),
        ]));

        return [
            'status' => 'success',
            'booking_id' => $booking->id,
            'customer_phone' => $booking->customer_phone,
            'technician_name' => "{$employee->first_name} {$employee->last_name}",
            'scheduled_start' => $requestedTimeCarbon->toIso8601String(),
            'message' => "Booking #{$booking->id} confirmed with {$employee->first_name} for {$requestedTimeCarbon->format('l, M j \a\t g:i A')}.",
        ];
    }
}
