<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;

class CollectionCreated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     * @param CollectionCreatedPolkadart $event
     * @param Model|null $transaction
     * @param array|null $extra
     */
    public function __construct(CollectionCreatedPolkadart $event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

//        $this->model = $collection;

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel($event->owner),
            new PlatformAppChannel(),
        ];
    }
}
