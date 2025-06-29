<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\BalanceSet as BalanceSetPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class BalanceSet extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(BalanceSetPolkadart $event, ?Transaction $transaction = null)
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
