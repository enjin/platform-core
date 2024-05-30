<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Frozen as CollectionFrozenPolkadart;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionMutated as CollectionMutatedPolkadart;

class CollectionMutated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     * @param CollectionMutatedPolkadart $event
     * @param Model|null $transaction
     * @param array|null $extra
     */
    public function __construct(CollectionMutatedPolkadart $event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);
        +

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel($collection->owner->address),
            new PlatformAppChannel(),
        ];
    }
}
