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
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employees')]
#[Fillable('tenant_id', 'first_name', 'last_name', 'phone', 'skills', 'notification_preference', 'user_id')]
#[Casts(['skills' => 'array'])]
class Employee extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Boot model and register event listeners.
     */
    protected static function booted(): void
    {
        static::deleted(function (Employee $employee) {
            $user = auth()->user();
            AuditLog::create([
                'tenant_id' => $employee->tenant_id,
                'user_id' => $user?->id,
                'action' => 'technician_removed',
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'browser_agent' => request()->userAgent() ?: 'System/CLI',
                'payload' => [
                    'id' => $employee->id,
                    'name' => "{$employee->first_name} {$employee->last_name}",
                    'phone' => $employee->phone,
                ],
            ]);
        });
    }

    /**
     * Get the tenant that owns the employee.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user account associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the availabilities for the employee.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * Get the bookings for the employee.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
