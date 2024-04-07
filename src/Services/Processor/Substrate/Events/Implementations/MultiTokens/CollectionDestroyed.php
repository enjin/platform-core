<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionDestroyed as CollectionDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionDestroyed as CollectionDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionDestroyed extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionDestroyedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($collectionId = $event->collectionId);
        $collection->delete();

        Log::info("Collection #{$collectionId} (id {$collection->id}) was destroyed.");

        CollectionDestroyedEvent::safeBroadcast(
            $collection,
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }
}
