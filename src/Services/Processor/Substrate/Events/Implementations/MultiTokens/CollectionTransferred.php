<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionTransferred as CollectionTransferredEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionTransferred as CollectionTransferredPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionTransferred extends SubstrateEvent
{
    /** @var CollectionTransferredPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        // Fails if collection is not found
        $collection = $this->getCollection($this->event->collectionId);
        $owner = $this->firstOrStoreAccount($this->event->owner);

        $collection->owner_wallet_id = $owner->id;
        $collection->pending_transfer = null;
        $collection->save();
    }

    public function log(): void
    {
        Log::debug("Collection {$this->event->collectionId} owner changed to {$this->event->owner}.");
    }

    public function broadcast(): void
    {
        CollectionTransferredEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
