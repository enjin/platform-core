<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenTransferred;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Transferred as TransferredPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Transferred extends SubstrateEvent
{
    /** @var TransferredPolkadart */
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
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $this->event->tokenId);
        $fromAccount = $this->firstOrStoreAccount($this->event->from);
        $toAccount = $this->firstOrStoreAccount($this->event->to);

        TokenAccount::firstWhere([
            'wallet_id' => $fromAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->decrement('balance', $this->event->amount);

        TokenAccount::firstWhere([
            'wallet_id' => $toAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->increment('balance', $this->event->amount);
    }

    public function log(): void
    {
        Log::debug(sprintf(
            '%s transferred %s of token %s from collection %s to %s.',
            $this->event->from,
            $this->event->amount,
            $this->event->tokenId,
            $this->event->collectionId,
            $this->event->to,
        ));
    }

    public function broadcast(): void
    {
        TokenTransferred::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
