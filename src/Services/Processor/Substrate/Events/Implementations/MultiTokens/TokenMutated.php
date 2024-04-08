<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMutated as TokenMutatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenMutated as TokenMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenMutated extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenMutatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);
        $attributes = [];

        if ($event->listingForbidden) {
            $attributes['listing_forbidden'] = $event->listingForbidden;
        }

        if ($event->behaviorMutation === 'SomeMutation') {
            $attributes['is_currency'] = $event->isCurrency;
            $attributes['royalty_wallet_id'] = null;
            $attributes['royalty_percentage'] = null;

            if ($event->beneficiary) {
                $attributes['royalty_wallet_id'] = $this->firstOrStoreAccount($event->beneficiary)?->id;
                $attributes['royalty_percentage'] = number_format($event->percentage / 1000000000, 9);
            }
        }

        $token->fill($attributes)->save();

        Log::info("Token #{$token->token_chain_id} (id {$token->id}) of Collection #{$collection->collection_chain_id} (id {$collection->id}) was updated.");

        TokenMutatedEvent::safeBroadcast(
            $token,
            $event->getParams(),
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
