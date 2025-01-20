<?php

namespace Enjin\Platform\Events\Substrate\System;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\CodeUpdated as CodeUpdatedPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class CodeUpdated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(CodeUpdatedPolkadart $event)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast();

        $this->broadcastChannels = [
            new PlatformAppChannel(),
        ];
    }
}
