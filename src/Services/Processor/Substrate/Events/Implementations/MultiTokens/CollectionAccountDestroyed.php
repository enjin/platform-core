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
        if (!$event instanceof CollectionAccountDestroyedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $account = $this->firstOrStoreAccount($event->account);

        CollectionAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
        ])->delete();

        Log::info(
            sprintf(
                'CollectionAccount of Collection #%s (id %s) and account %s was deleted.',
                $event->collectionId,
                $collection->id,
                $account->address ?? 'unknown',
            )
        );

        CollectionAccountDestroyedEvent::safeBroadcast(
            $this->getCollection($event->collectionId),
            $account,
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
