<?php

namespace Enjin\Platform\Observers;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Events\Global\WalletCreated;
use Enjin\Platform\Models\Indexer\Account;
use Illuminate\Support\Facades\Cache;

class WalletObserver
{
    /**
     * Listen to the saved event.
     */
    public function saved(Account $wallet): void
    {
        if ($wallet->managed) {
            Cache::forget(PlatformCache::MANAGED_ACCOUNTS->key());
        }
    }

    public function created(Account $wallet): void
    {
        WalletCreated::dispatch($wallet);
    }
}
