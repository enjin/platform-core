<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Reserved as ReservedPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class Reserved extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(ReservedPolkadart $event, ?Transaction $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel($event->who),
            new PlatformAppChannel(),
        ];
    }
}
