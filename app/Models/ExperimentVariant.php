<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('experiment_variants')]
#[Fillable('experiment_id', 'version', 'prompt_instructions', 'model_provider', 'call_count', 'booking_count')]
#[Casts(['call_count' => 'integer', 'booking_count' => 'integer'])]
class ExperimentVariant extends Model
{
    use HasAttributeCasts, HasFactory;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get the experiment that owns the variant.
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }
}
