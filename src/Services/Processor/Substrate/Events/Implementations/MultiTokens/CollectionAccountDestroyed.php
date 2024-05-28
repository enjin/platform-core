<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountDestroyed as CollectionAccountDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionAccountDestroyed as CollectionAccountDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionAccountDestroyed extends SubstrateEvent
{
    /** @var CollectionAccountDestroyedPolkadart */
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
        $account = $this->firstOrStoreAccount($this->event->account);

        CollectionAccount::where([
            'wallet_id' => $account->id ?? null,
            'collection_id' => $collection->id,
        ])->delete();
    }

    public function log(): void
    {
        Log::info(
            sprintf(
                'Account %s of Collection %s was deleted.',
                $this->event->account,
                $this->event->collectionId,
            )
        );
    }

    public function broadcast(): void
    {
        CollectionAccountDestroyedEvent::safeBroadcast(
            $this->event->collectionId,
            $this->event->account,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }
}
