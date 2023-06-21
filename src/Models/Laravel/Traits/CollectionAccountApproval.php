<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CollectionAccountApproval
{
    /**
     * The collection account relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(CollectionAccount::class, 'collection_account_id');
    }

    /**
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
