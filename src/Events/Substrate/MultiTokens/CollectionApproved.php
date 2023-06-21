<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class CollectionApproved extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(string $collectionId, string $operator, ?string $expiration, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collectionId,
            'operator' => $operator,
            'expiration' => $expiration,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$collectionId}"),
            new Channel($operator),
            new PlatformAppChannel(),
        ];
    }
}
