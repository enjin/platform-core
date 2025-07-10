<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Transfer as TransferPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class Transfer extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(TransferPolkadart $event, ?Transaction $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
            'transactionHash' => $transaction?->transaction_chain_hash,
        ]);

        $this->broadcastChannels = [
            new Channel($event->from),
            new Channel($event->to),
            new PlatformAppChannel(),
        ];
    }
}
