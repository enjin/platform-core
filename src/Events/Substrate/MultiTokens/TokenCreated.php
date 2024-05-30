<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenCreated as TokenCreatedPolkadart;

class TokenCreated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     * @param TokenCreatedPolkadart $event
     * @param Model|null $transaction
     * @param array|null $extra
     */
    public function __construct(TokenCreatedPolkadart $event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->model = $token;

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);
        +

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel($event->issuer),
            new Channel($token->collection->owner->address),
            new PlatformAppChannel(),
        ];
    }
}
