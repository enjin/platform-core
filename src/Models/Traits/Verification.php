<?php

namespace Enjin\Platform\Models\Traits;

use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait Verification
{
    /**
     * The wallet relationship.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
}
