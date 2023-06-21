<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenMinted extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $token, Model $issuer, Model $recipient, string $amount, ?Model $transaction = null)
    {
        parent::__construct();

        $this->model = $token;

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $token->collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'issuer' => $issuer->address,
            'recipient' => $recipient->address,
            'amount' => $amount,
        ];

        $this->broadcastChannels = [
            new Channel($token->collection->owner->address),
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new Channel($recipient),
            new PlatformAppChannel(),
        ];
    }
}
