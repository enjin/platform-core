<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;

class PlatformEventCached extends PlatformEvent
{
    public function __construct(public $cachedEvent)
    {
        parent::__construct();
    }
}
