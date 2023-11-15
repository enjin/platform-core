<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class TokenUnreserved extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Model $collection, Model $token, Model $wallet, Model $event, ?Model $transaction = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'idempotencyKey' => $transaction?->idempotency_key,
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $token->token_chain_id,
            'wallet' => $wallet->address,
            'amount' => $event->amount,
            'reserveId' => HexConverter::hexToString($event->reserveId),
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$this->broadcastData['collectionId']}"),
            new Channel("token;{$this->broadcastData['tokenId']}"),
            new Channel($wallet->address),
            new PlatformAppChannel(),
        ];
    }
}
