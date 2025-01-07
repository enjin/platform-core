<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;
use Illuminate\Database\Eloquent\Model;

class WalletCreated extends PlatformEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Model $model)
    {
        parent::__construct();
    }
}
