<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenDestroyed as TokenDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenDestroyed as TokenDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenDestroyed extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenDestroyedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($collectionId = $event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $tokenId = $event->tokenId);
        $token->delete();

        Log::info("Token #{$tokenId} in Collection ID {$collectionId} was destroyed.");

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        TokenDestroyedEvent::safeBroadcast(
            $token,
            $this->firstOrStoreAccount($extrinsic?->signer),
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }
}
