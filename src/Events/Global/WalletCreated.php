<?php

namespace Enjin\Platform\Events\Global;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Traits\HasCustomQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class WalletCreated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $wallet)
    {
        parent::__construct();

        $this->model = $wallet;

        $this->broadcastData = [
            'id' => $wallet->id,
            'publicKey' => $wallet->public_key,
        ];
        $this->broadcastChannels = [
            new Channel($wallet->public_key),
        ];
    }
}
