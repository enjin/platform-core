<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionMutated as CollectionMutatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionMutated as CollectionMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionMutated extends SubstrateEvent
{

    /** @var CollectionMutatedPolkadart */
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
        $attributes = [];
        $royalties = [];

        if (!is_null($this->event->owner)) {
            $attributes['pending_transfer'] = $this->event->owner;
        }

        if ($this->event->royalty === 'SomeMutation') {
            if ($beneficiary = $this->event->beneficiary) {
                $attributes['royalty_wallet_id'] = $this->firstOrStoreAccount($beneficiary)->id;
                $attributes['royalty_percentage'] = number_format($this->event->percentage / 1000000000, 9);
            } else {
                $attributes['royalty_wallet_id'] = null;
                $attributes['royalty_percentage'] = null;
            }
        }

        if (!is_null($currencies = $this->event->explicitRoyaltyCurrencies)) {
            foreach ($currencies as $currency) {
                $royalties[] = new CollectionRoyaltyCurrency([
                    'currency_collection_chain_id' => $currency['collection_id'],
                    'currency_token_chain_id' => $currency['token_id'],
                ]);
            }

            $collection->royaltyCurrencies()->delete();
            $collection->royaltyCurrencies()->saveMany($royalties);
        }

        $collection->fill($attributes)->save();
    }

    public function log(): void
    {
        Log::info("Collection {$this->event->collectionId} was mutated.");
    }

    public function broadcast(): void
    {
        CollectionMutatedEvent::safeBroadcast(
            $this->event->collectionId,
            $this->event->getParams(),
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
