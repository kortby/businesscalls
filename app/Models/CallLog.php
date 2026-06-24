<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('call_logs')]
#[Fillable('tenant_id', 'call_id', 'status', 'customer_phone', 'transcript', 'recording_url', 'summary', 'duration', 'csat_score')]
class CallLog extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns the call log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Calculate and store the CSAT score using the formula.
     *
     * @param  array<int>  $ratings
     */
    public function calculateCsatScore(array $ratings): ?float
    {
        if (empty($ratings)) {
            return null;
        }

        $sum = array_sum($ratings);
        $K = count($ratings);

        $score = ($sum / (5 * $K)) * 100;

        $this->csat_score = $score;
        $this->save();

        return $score;
    }
}
