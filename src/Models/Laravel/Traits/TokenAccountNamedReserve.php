<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\TokenAccount;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TokenAccountNamedReserve
{
    /**
     * The token account relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(TokenAccount::class, 'token_account_id');
    }
}
