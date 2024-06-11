<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenCreated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $token, Model $issuer, ?Model $transaction = null)
    {
        parent::__construct();

        $this->model = $token;

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $token->collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'initialSupply' => $token->supply,
            'issuer' => $issuer->address,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new PlatformAppChannel(),
            new Channel($token->collection->owner->address),
            new Channel($issuer->address),
        ];
    }
}
