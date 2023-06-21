<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenAccountFrozen extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $tokenAccount, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $tokenAccount->collection->collection_chain_id,
            'tokenId' => $tokenAccount->token->token_chain_id,
            'tokenAccount' => $tokenAccount->wallet->address,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel($this->broadcastData['tokenAccount']),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new PlatformAppChannel(),
        ];
    }
}
