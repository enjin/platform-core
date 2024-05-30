<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeSet as CollectionAttributeSetPolkadart;

class CollectionAttributeSet extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(CollectionAttributeSetPolkadart $event, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel($collection->owner->address),
            new PlatformAppChannel(),
        ];
    }
}
