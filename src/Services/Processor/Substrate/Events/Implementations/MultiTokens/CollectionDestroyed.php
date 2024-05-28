<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionDestroyed as CollectionDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionDestroyed as CollectionDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionDestroyed extends SubstrateEvent
{
    /** @var CollectionDestroyedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$event instanceof CollectionDestroyedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $collection->delete();

        Log::info("Collection #{$event->collectionId} (id {$collection->id}) was destroyed.");

        CollectionDestroyedEvent::safeBroadcast(
            $collection,
            $this->getTransaction($block, $event->extrinsicIndex)
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
