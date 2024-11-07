<?php

namespace Enjin\Platform\Events\Substrate\Commands;

use Enjin\Platform\Events\PlatformEvent;
use Enjin\Platform\Traits\HasCustomQueue;

class PlatformSyncError extends PlatformEvent
{
    use HasCustomQueue;

    public function __construct(public string $message)
    {
        parent::__construct();
    }
}
