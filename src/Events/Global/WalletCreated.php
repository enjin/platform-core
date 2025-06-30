<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Traits\HasCustomQueue;

class WalletCreated extends PlatformEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Wallet $model)
    {
        parent::__construct();
    }
}
