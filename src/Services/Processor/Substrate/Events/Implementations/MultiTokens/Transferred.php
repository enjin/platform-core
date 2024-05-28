<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenTransferred;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Transferred as TransferredPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Transferred extends SubstrateEvent
{
    /** @var TransferredPolkadart */
    protected PolkadartEvent $event;

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
        Log::info(sprintf(
            'Transferred %s units of token #%s (id: %s) in collection #%s (id: %s) to %s (id: %s).',
            $this->event->amount,
            $this->event->tokenId,
            $token->id,
            $this->event->collectionId,
            $collection->id,
            $toAccount->address ?? 'unknown',
            $toAccount->id ?? 'unknown',
        ));
    }

    public function broadcast(): void
    {
        TokenTransferred::safeBroadcast(
            $token,
            $fromAccount,
            $toAccount,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
