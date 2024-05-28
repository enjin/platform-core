<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenDestroyed as TokenDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenDestroyed as TokenDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenDestroyed extends SubstrateEvent
{
    /** @var TokenDestroyedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($collectionId = $event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $tokenId = $event->tokenId);
        $token->delete();


        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

    }

    public function log(): void
    {
        Log::info("Token #{$tokenId} in Collection ID {$collectionId} was destroyed.");

    }

    public function broadcast(): void
    {
        TokenDestroyedEvent::safeBroadcast(
            $token,
            $this->firstOrStoreAccount($extrinsic?->signer),
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }
}
