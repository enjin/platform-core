<?php

namespace Enjin\Platform\Observers;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Support\Facades\Cache;

class WalletObserver
{
    /**
     * Listen to the Wallet created event.
     */
    public function saving(Wallet $wallet): void
    {
        if ($wallet->managed === true) {
            Cache::forget(PlatformCache::MANAGED_ACCOUNTS->key());
        }
    }
}
