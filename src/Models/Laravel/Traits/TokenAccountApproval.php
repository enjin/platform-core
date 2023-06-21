<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TokenAccountApproval
{
    /**
     * The token account relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(TokenAccount::class, 'token_account_id');
    }

    /**
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
