<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class CollectionCreated extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(CollectionCreatedPolkadart $event, ?Model $transaction = null, ?array $extra = null, ?Model $collection = null)
    {
        parent::__construct();

        $this->model = $collection;

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
