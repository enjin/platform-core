<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;

class PlatformBlockIngesting extends PlatformEvent
{
    use HasCustomQueue;
}
