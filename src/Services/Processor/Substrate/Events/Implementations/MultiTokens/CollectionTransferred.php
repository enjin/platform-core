<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionTransferred as CollectionTransferredEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionTransferred as CollectionTransferredPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionTransferred extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionTransferredPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if collection is not found
        $collection = $this->getCollection($event->collectionId);
        $owner = $this->firstOrStoreAccount($event->owner);

        $collection->owner_wallet_id = $owner->id;
        $collection->pending_transfer = null;
        $collection->save();

        Log::info("Collection #{$event->collectionId} (id {$collection->id}) owner changed to {$owner->public_key} (id {$owner->id}).");

        CollectionTransferredEvent::safeBroadcast(
            $collection,
            $owner->public_key,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
