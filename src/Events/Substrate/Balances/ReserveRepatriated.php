<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\ReserveRepatriated as ReserveRepatriatedPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class ReserveRepatriated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(ReserveRepatriatedPolkadart $event, ?Transaction $transaction = null)
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
