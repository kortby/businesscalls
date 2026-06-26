<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
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
}
