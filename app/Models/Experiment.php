<?php

namespace App\Models;

use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('experiments')]
#[Fillable('tenant_id', 'name', 'status', 'traffic_split')]
#[Casts(['traffic_split' => 'integer'])]
class Experiment extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Get the variants for the experiment.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class);
    }

    /**
     * Calculate chi-square significance score:
     * Chi2 = Sum over A,B of (O_i - E_i)^2 / E_i
     */
    public function calculateChiSquare(): float
    {
        $variants = $this->variants()->get();
        $variantA = $variants->where('version', 'A')->first();
        $variantB = $variants->where('version', 'B')->first();

        if (! $variantA || ! $variantB) {
            return 0.0;
        }

        $callsA = $variantA->call_count;
        $bookingsA = $variantA->booking_count;

        $callsB = $variantB->call_count;
        $bookingsB = $variantB->booking_count;

        $totalCalls = $callsA + $callsB;
        $totalBookings = $bookingsA + $bookingsB;

        if ($totalCalls === 0) {
            return 0.0;
        }

        $avgConversion = (float) $totalBookings / $totalCalls;

        if ($avgConversion === 0.0) {
            return 0.0;
        }

        $expectedA = $callsA * $avgConversion;
        $expectedB = $callsB * $avgConversion;

        $chiSquare = 0.0;

        if ($expectedA > 0.0) {
            $chiSquare += pow($bookingsA - $expectedA, 2) / $expectedA;
        }
        if ($expectedB > 0.0) {
            $chiSquare += pow($bookingsB - $expectedB, 2) / $expectedB;
        }

        return $chiSquare;
    }
}
