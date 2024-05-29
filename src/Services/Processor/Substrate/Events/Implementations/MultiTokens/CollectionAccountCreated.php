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
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $account = $this->firstOrStoreAccount($this->event->account);

        CollectionAccount::updateOrCreate([
            'wallet_id' => $account->id ?? null,
            'collection_id' => $collection->id,
        ], [
            'is_frozen' => false,
            'account_count' => 0,
        ]);
    }

    public function log(): void
    {
        Log::info(
            sprintf(
                'Account %s of Collection %s was created.',
                $this->event->account,
                $this->event->collectionId,
            )
        );
    }

    public function broadcast(): void
    {
        CollectionAccountCreatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
