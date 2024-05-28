<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class DustLost extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(string $account, string $amount, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'account' => $account,
            'amount' => $amount,
        ];

        $this->broadcastChannels = [
            new Channel($account),
            new PlatformAppChannel(),
        ];
    }
}
