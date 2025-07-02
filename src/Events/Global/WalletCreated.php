<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Traits\HasCustomQueue;

class WalletCreated extends PlatformEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Account $model)
    {
        parent::__construct();
    }
}
