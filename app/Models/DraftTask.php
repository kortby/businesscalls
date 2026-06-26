<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('draft_tasks')]
#[Fillable([
    'tenant_id',
    'booking_id',
    'call_id',
    'task_type',
    'description',
    'status',
])]
class DraftTask extends Model
{
    use BelongsToTenant, HasAttributeCasts;

    /**
     * Get the tenant that owns the draft task.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the booking linked to this draft task.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
