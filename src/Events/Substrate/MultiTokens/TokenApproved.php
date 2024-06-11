<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenApproved extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        string $collectionId,
        string $tokenId,
        string $operator,
        string $amount,
        ?string $expiration,
        ?Model $transaction = null
    ) {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collectionId,
            'tokenId' => $tokenId,
            'operator' => $operator,
            'amount' => $amount,
            'expiration' => $expiration,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$collectionId}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new Channel($operator),
            new PlatformAppChannel(),
        ];
    }
}
