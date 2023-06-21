<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TransactionCreated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $transaction)
    {
        parent::__construct();

        $this->model = $transaction;

        $this->broadcastData = [
            'id' => $transaction->id,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'idempotencyKey' => $transaction->idempotency_key,
        ];

        $this->broadcastChannels = [
            new Channel($transaction->wallet->address),
        ];
    }
}
