<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenAccountDestroyed extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $collection, Model $token, Model $wallet, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'wallet' => $wallet->address,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new Channel($wallet->address),
            new PlatformAppChannel(),
        ];
    }
}
