<?php

namespace Enjin\Platform\Models\Traits;

use Enjin\Platform\Models\Indexer\Account;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait Verification
{
    /**
     * The wallet relationship.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Account::class);
    }
}
