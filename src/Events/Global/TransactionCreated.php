<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Traits\HasCustomQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TransactionCreated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct($event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->model = $transaction;

        $this->broadcastData = [
            'id' => $transaction->id,
            'method' => $transaction->method,
            'state' => $transaction->state,
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
