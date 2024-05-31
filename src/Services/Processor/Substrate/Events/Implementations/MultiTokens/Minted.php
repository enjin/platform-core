<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMinted;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Minted as MintedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Minted extends SubstrateEvent
{
    /** @var MintedPolkadart */
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
        $recipient = $this->firstOrStoreAccount($this->event->recipient);

        $token->update([
            'supply', gmp_strval(gmp_add($token->supply, $this->event->amount)) ?? 0,
        ]);

        TokenAccount::where([
            'collection_id' => $collection->id,
            'token_id' => $token->id,
            'wallet_id' => $recipient->id,
        ])->increment('balance', $this->event->amount);
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Minted %s units of collection %s, token %s to %s.',
            $this->event->amount,
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->recipient,
        ));
    }

    public function broadcast(): void
    {
        TokenMinted::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
