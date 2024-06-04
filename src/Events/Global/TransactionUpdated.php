<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TransactionUpdated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'id' => $transaction->id,
            'method' => $transaction->method,
            'state' => $transaction->state,
            'result' => $transaction->result,
            'transactionId' => $transaction->transaction_chain_id,
            'transactionHash' => $transaction->transaction_chain_hash,
            'idempotencyKey' => $transaction->idempotency_key,
        ];

        $address = $transaction->wallet?->address ?? Account::daemon()->address;

        if ($address == null) {
            return;
        }

        $this->broadcastChannels = [
            new Channel($address),
        ];
    }
}
