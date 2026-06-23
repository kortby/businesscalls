<?php

namespace App\Concerns;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasConversations
{
    /**
     * Get the conversations associated with the user.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
