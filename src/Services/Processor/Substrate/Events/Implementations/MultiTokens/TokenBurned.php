<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenBurned as TokenBurnedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Burned as BurnedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenBurned extends SubstrateEvent
{
    /** @var BurnedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $this->extra = ['collection_owner' => $collection->owner->public_key];

        $account = $this->firstOrStoreAccount($this->event->account);

        $token = Token::firstWhere([
            'collection_id' => $collection->id,
            'token_chain_id' => $this->event->tokenId,
        ]);

        $token?->decrement('supply', $this->event->amount);

        TokenAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token?->id,
        ])?->decrement('balance', $this->event->amount);
    }

    public function log(): void
    {
        Log::debug(sprintf(
            '%s burned %s units of token %s from collection %s.',
            $this->event->account,
            $this->event->amount,
            $this->event->tokenId,
            $this->event->collectionId,
        ));
    }

    public function broadcast(): void
    {
        TokenBurnedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
