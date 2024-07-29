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
    public function __construct(Model $from, Model $to, string $amount, string $destination, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'transactionHash' => $transaction?->transaction_chain_hash,
            'from' => $from->public_key,
            'to' => $to->public_key,
            'amount' => $amount,
            'destination' => $destination,
        ];

        $this->broadcastChannels = [
            new Channel($from->public_key),
            new Channel($to->public_key),
            new PlatformAppChannel(),
        ];
    }
}
