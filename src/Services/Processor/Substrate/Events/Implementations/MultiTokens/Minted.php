<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMinted;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Minted as MintedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Minted extends SubstrateEvent
{
    /** @var MintedPolkadart */
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

        $transaction = $this->getTransaction($this->block, $this->event->extrinsicIndex);
        $recipient = $this->firstOrStoreAccount($this->event->recipient);

        $token->update([
            'supply', gmp_strval(gmp_add($token->supply, $this->event->amount)) ?? 0,
        ]);

        $tokenAccount = TokenAccount::firstWhere([
            'wallet_id' => $recipient->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ]);
        $tokenAccount->increment('balance', $this->event->amount);




    }

    public function log(): void
    {
        Log::info(sprintf(
            'Minted %s units of Collection #%s (id: %s), Token #%s (id: %s) to %s (id: %s).',
            $event->amount,
            $event->collectionId,
            $collection->id,
            $event->tokenId,
            $token->id,
            $recipient->address ?? 'unknown',
            $recipient->id ?? 'unknown',
        ));
    }

    public function broadcast(): void
    {
        TokenMinted::safeBroadcast(
            $token,
            $this->firstOrStoreAccount($event->issuer),
            $recipient,
            $event->amount,
            $transaction,
        );
    }
}
