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
        if (!$event instanceof TransferredPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);

        $fromAccount = $this->firstOrStoreAccount($event->from);
        $toAccount = $this->firstOrStoreAccount($event->to);

        TokenAccount::firstWhere([
            'wallet_id' => $fromAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->decrement('balance', $event->amount);

        TokenAccount::firstWhere([
            'wallet_id' => $toAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->increment('balance', $event->amount);

        Log::info(sprintf(
            'Transferred %s units of token #%s (id: %s) in collection #%s (id: %s) to %s (id: %s).',
            $event->amount,
            $event->tokenId,
            $token->id,
            $event->collectionId,
            $collection->id,
            $toAccount->address ?? 'unknown',
            $toAccount->id ?? 'unknown',
        ));

        TokenTransferred::safeBroadcast(
            $token,
            $fromAccount,
            $toAccount,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }

    public function log()
    {
        // TODO: Implement log() method.
    }

    public function broadcast()
    {
        // TODO: Implement broadcast() method.
    }
}
