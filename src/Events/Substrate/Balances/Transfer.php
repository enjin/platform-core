<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Transfer as TransferPolkadart;

class Transfer extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(TransferPolkadart $event, ?Model $transaction = null, ?array $extra = null)
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
