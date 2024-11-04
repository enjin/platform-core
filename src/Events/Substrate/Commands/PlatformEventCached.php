<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;

class PlatformEventCached extends PlatformEvent
{
    use HasCustomQueue;

    public function __construct(public $cachedEvent)
    {
        parent::__construct();
    }
}
