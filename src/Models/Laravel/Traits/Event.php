<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Event
{
    /**
     * The transaction relationship.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
