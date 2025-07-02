<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Traits\HasCustomQueue;
use Illuminate\Broadcasting\Channel;

class TransactionUpdated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct($event, ?Transaction $transaction = null, ?array $extra = null)
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

        $publicKey = $transaction->wallet?->public_key ?? Address::daemonPublicKey();

        if ($publicKey == null) {
            return;
        }

        $this->broadcastChannels = [
            new Channel($publicKey),
        ];
    }
}
