<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountCreated as CollectionAccountCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionAccountCreated as CollectionAccountCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionAccountCreated extends SubstrateEvent
{
    /** @var CollectionAccountCreatedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$event instanceof CollectionAccountCreatedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $account = $this->firstOrStoreAccount($event->account);

        $collectionAccount = CollectionAccount::create([
            'wallet_id' => $account->id ?? null,
            'collection_id' => $collection->id,
            'is_frozen' => false,
            'account_count' => 0,
        ]);

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s was created.',
                $collectionAccount->id,
                $event->collectionId,
                $collection->id,
                $account->address ?? 'unknown',
            )
        );

        CollectionAccountCreatedEvent::safeBroadcast(
            $event->collectionId,
            $account,
            $this->getTransaction($block, $event->extrinsicIndex),
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
