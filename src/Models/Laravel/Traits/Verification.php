<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Wallet;
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
