<?php

namespace Enjin\Platform\Events\Substrate\Balances;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class Endowed extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $account, string $freeBalance, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'account' => $account->address,
            'freeBalance' => $freeBalance,
        ];

        $this->broadcastChannels = [
            new Channel($account->address),
            new PlatformAppChannel(),
        ];
    }
}
