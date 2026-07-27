<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use App\Jobs\SendTechnicianAlertJob;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bookings')]
#[Fillable('tenant_id', 'employee_id', 'customer_phone', 'job_details', 'booking_notes', 'status', 'scheduled_start', 'en_route_at', 'on_site_at', 'completed_at', 'travel_time', 'is_test_mode', 'priority_state', 'required_certification', 'latitude', 'longitude', 'triage_notes', 'appliance_brand', 'appliance_age', 'urgency_markers', 'booking_hash')]
#[Casts(['scheduled_start' => 'datetime', 'en_route_at' => 'datetime', 'on_site_at' => 'datetime', 'completed_at' => 'datetime', 'is_test_mode' => 'boolean', 'latitude' => 'double', 'longitude' => 'double', 'urgency_markers' => 'array'])]
class Booking extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the booking.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the employee that is assigned to the booking.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Ideal automated workflow to assign task, create work order job, and notify technician.
     */
    public function assignTaskToTechnician(): ServiceJob
    {
        $tenantId = $this->tenant_id;
        $employee = $this->employee;

        // 1. Resolve or create Customer by phone number
        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'phone' => $this->customer_phone,
            ],
            [
                'name' => 'Customer ('.$this->customer_phone.')',
                'notes' => 'Created automatically from Booking #'.$this->id,
            ]
        );

        // 2. Create ServiceJob linked to Customer and assigned Employee
        $job = ServiceJob::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'employee_id' => $this->employee_id,
            'title' => $this->job_details ?: 'Service Job',
            'description' => trim(($this->triage_notes ?? '')."\n".($this->booking_notes ?? '')),
            'status' => 'pending',
            'steps' => [
                ['name' => 'Initial Inspection & Diagnosis', 'completed' => false],
                ['name' => 'Execute Repairs / Maintenance', 'completed' => false],
                ['name' => 'Customer Sign-off & Invoicing', 'completed' => false],
            ],
        ]);

        // 3. Create DraftTask for technician task list tracking
        DraftTask::create([
            'tenant_id' => $tenantId,
            'booking_id' => $this->id,
            'task_type' => 'technician_dispatch',
            'description' => "Assigned dispatch job to {$employee?->first_name}: {$this->job_details} at ".($this->scheduled_start ? $this->scheduled_start->format('Y-m-d H:i') : 'asap'),
            'status' => 'pending',
        ]);

        // 4. Dispatch notification job to notify technician via Voice/SMS
        SendTechnicianAlertJob::dispatch($this);

        return $job;
    }
}
