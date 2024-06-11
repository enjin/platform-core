<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Laravel\Collection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class CollectionAccountDestroyed extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Collection $collection, Model $wallet, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collection->collection_chain_id,
            'wallet' => $wallet->address,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel($wallet->address),
            new PlatformAppChannel(),
        ];
    }
}
