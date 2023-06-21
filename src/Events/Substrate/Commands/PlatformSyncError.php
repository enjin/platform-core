<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;

class PlatformSyncError extends PlatformEvent
{
    public function __construct(public string $message)
    {
        parent::__construct();
    }
}
