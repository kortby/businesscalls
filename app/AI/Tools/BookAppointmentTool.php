<?php

namespace App\AI\Tools;

class BookAppointmentTool
{
    public function description(): string
    {
        return 'Book an appointment for a customer with a specific technician or slot.';
    }

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
                'description' => 'Service job description (e.g. AC repair, water heater check).',
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

    public function handle(
        string $tenant_id,
        string $customer_phone,
        string $job_details,
        string $scheduled_start,
        ?int $employee_id = null
    ): array {
        $createBookingTool = new CreateBookingTool;

        return $createBookingTool->handle(
            $tenant_id,
            $customer_phone,
            $job_details,
            $scheduled_start,
            $employee_id
        );
    }
}
