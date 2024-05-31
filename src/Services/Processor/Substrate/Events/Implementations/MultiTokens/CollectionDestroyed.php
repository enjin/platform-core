<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionDestroyed as CollectionDestroyedEvent;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionDestroyed as CollectionDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionDestroyed extends SubstrateEvent
{
    /** @var CollectionDestroyedPolkadart */
    protected Event $event;

    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        //         $this->extra = ['collection_owner' => $collection->owner->public_key];
        Collection::where('collection_chain_id', $this->event->collectionId)
            ->delete();
    }

    public function log(): void
    {
        Log::info("Collection {$this->event->collectionId} was destroyed.");
    }

    public function broadcast(): void
    {
        CollectionDestroyedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
