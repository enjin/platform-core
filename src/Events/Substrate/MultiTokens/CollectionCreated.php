<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class CollectionCreated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $collection, ?Model $transaction = null)
    {
        parent::__construct();

        $this->model = $collection;

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collection->collection_chain_id,
            'owner' => $collection->owner->address,
        ];

        $this->broadcastChannels = [
            new Channel($collection->owner->address),
            new PlatformAppChannel(),
        ];
    }
}
