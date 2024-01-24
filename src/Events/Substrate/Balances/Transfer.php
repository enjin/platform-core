<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class Transfer extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $from, Model $to, string $amount, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'from' => $from->address,
            'to' => $to->address,
            'amount' => $amount,
        ];

        $this->broadcastChannels = [
            new Channel($from->address),
            new Channel($to->address),
            new PlatformAppChannel(),
        ];
    }
}
