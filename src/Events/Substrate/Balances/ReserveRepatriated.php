<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\ReserveRepatriated as ReserveRepatriatedPolkadart;

class ReserveRepatriated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(ReserveRepatriatedPolkadart $event, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel($event->from),
            new Channel($event->to),
            new PlatformAppChannel(),
        ];
    }
}
