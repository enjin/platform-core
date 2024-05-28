<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class BalanceSet extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(string $who, string $free, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'who' => $who,
            'free' => $free,
        ];

        $this->broadcastChannels = [
            new Channel($who),
            new PlatformAppChannel(),
        ];
    }
}
