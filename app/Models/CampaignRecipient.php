<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('campaign_recipients')]
#[Fillable('campaign_id', 'phone_number', 'name', 'call_id', 'status')]
class CampaignRecipient extends Model
{
    use HasFactory;

    /**
     * Get the campaign that owns the recipient.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OutboundCampaign::class, 'campaign_id');
    }
}
