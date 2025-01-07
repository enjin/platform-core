<?php

namespace Enjin\Platform\Observers;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Events\Global\WalletCreated;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Support\Facades\Cache;

class WalletObserver
{
    /**
     * Listen to the Wallet saved event.
     */
    public function saved(Wallet $wallet): void
    {
        if ($wallet->managed) {
            Cache::forget(PlatformCache::MANAGED_ACCOUNTS->key());
        }
    }

    public function created(Wallet $wallet): void
    {
        WalletCreated::dispatch($wallet);
    }
}
