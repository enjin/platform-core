<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Event;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Transaction
{
    /**
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_public_key', 'public_key');
    }

    /**
     * The events relationship.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
