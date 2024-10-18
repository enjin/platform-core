<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMutated as TokenMutatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenMutated as TokenMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenMutated extends SubstrateEvent
{
    /** @var TokenMutatedPolkadart */
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
        $attributes = [];

        if ($this->event->anyoneCanInfuse !== null) {
            $attributes['anyone_can_infuse'] = $this->event->anyoneCanInfuse;
        }

        if ($this->event->listingForbidden != null) {
            $attributes['listing_forbidden'] = $this->event->listingForbidden;
        }

        if ($this->event->behavior === 'SomeMutation') {
            $attributes['is_currency'] = $this->event->isCurrency;
            $attributes['royalty_wallet_id'] = null;
            $attributes['royalty_percentage'] = null;

            if ($this->event->beneficiary) {
                $attributes['royalty_wallet_id'] = $this->firstOrStoreAccount($this->event->beneficiary)?->id;
                $attributes['royalty_percentage'] = number_format($this->event->percentage / 1000000000, 9);
            }
        }

        $token->fill($attributes)->save();
    }

    public function log(): void
    {
        Log::debug("Token {$this->event->tokenId} of collection {$this->event->collectionId} was mutated.");
    }

    public function broadcast(): void
    {
        TokenMutatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
