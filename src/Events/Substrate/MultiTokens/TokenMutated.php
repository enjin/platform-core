<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenMutated extends PlatformBroadcastEvent
{
    /**
     * Create instance.
     */
    public function __construct(Model $token, array $mutation, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $token->collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'mutation' => $mutation,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new PlatformAppChannel(),
            new Channel($token->collection->owner->address),
        ];
    }
}
