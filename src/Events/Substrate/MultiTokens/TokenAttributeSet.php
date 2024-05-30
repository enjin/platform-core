<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeSet as TokenAttributeSetPolkadart;

class TokenAttributeSet extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(TokenAttributeSetPolkadart $event, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel("token;{$event->collectionId}-{$event->tokenId}"),
            new Channel($token->collection->owner->address),
            new PlatformAppChannel(),
        ];
    }
}
