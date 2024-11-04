<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;

class PlatformSyncing extends PlatformEvent
{
    use HasCustomQueue;
}
