<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Minted as TokenMintedPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class TokenMinted extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(TokenMintedPolkadart $event, ?Model $transaction = null, ?array $extra = null, ?Model $token = null)
    {
        parent::__construct();

        $this->model = $token;

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel("token;{$event->collectionId}-{$event->tokenId}"),
            new Channel($extra['collection_owner']),
            new Channel($event->recipient),
            new Channel($event->issuer),
            new PlatformAppChannel(),
        ];
    }
}
