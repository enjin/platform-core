<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Transferred as TokenTransferredPolkadart;
use Enjin\Platform\Traits\HasCustomQueue;

class TokenTransferred extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(TokenTransferredPolkadart $event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("collection;{$event->collectionId}"),
            new Channel("token;{$event->collectionId}-{$event->tokenId}"),
            new Channel($event->from),
            new Channel($event->to),
            new Channel($event->operator),
            new Channel($extra['collection_owner']),
            new PlatformAppChannel(),
        ];
    }
}
