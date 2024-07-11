<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class Teleport extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(mixed $event, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'transactionHash' => $transaction?->transaction_chain_hash,
            'from' => $event->from,
            'to' => $event->to,
            'amount' => $event->amount,
            'destination' => $event->destination,
        ];

        $this->broadcastChannels = [
            new Channel($event->from),
            new Channel($event->to),
            new PlatformAppChannel(),
        ];
    }
}
