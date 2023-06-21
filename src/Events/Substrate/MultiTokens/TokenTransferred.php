<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenTransferred extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $token, Model $from, Model $recipient, string $amount, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $token->collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'from' => $from,
            'recipient' => $recipient,
            'amount' => $amount,
        ];

        $this->broadcastChannels = [
            new Channel($token->collection->owner->address),
            new Channel("collection;{$token->collection->collection_chain_id}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new Channel($from),
            new Channel($recipient),
            new PlatformAppChannel(),
        ];
    }
}
