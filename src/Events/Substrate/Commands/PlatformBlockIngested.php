<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;

class PlatformBlockIngested extends PlatformEvent
{
    use HasCustomQueue;
}
