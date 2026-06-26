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

#[Table('pricebooks')]
#[Fillable('tenant_id', 'item_code', 'description', 'flat_rate_price', 'category', 'diagnostic_required')]
#[Casts(['flat_rate_price' => 'decimal:2', 'diagnostic_required' => 'boolean'])]
class Pricebook extends Model
{
    use BelongsToTenant, HasAttributeCasts, HasFactory;

    /**
     * Get the tenant that owns this pricebook item.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
