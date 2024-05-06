<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionMutated as CollectionMutatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionMutated as CollectionMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CollectionMutated extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionMutatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if collection is not found
        $collection = $this->getCollection($event->collectionId);
        $attributes = [];
        $royalties = [];

        if (!is_null($event->owner)) {
            $attributes['pending_transfer'] = $event->owner;
        }

        if ($event->royalty === 'SomeMutation') {
            if ($beneficiary = $event->beneficiary) {
                $attributes['royalty_wallet_id'] = $this->firstOrStoreAccount($beneficiary)->id;
                $attributes['royalty_percentage'] = number_format($event->percentage / 1000000000, 9);
            } else {
                $attributes['royalty_wallet_id'] = null;
                $attributes['royalty_percentage'] = null;
            }
        }

        if (!is_null($currencies = $event->explicitRoyaltyCurrencies)) {
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

        Log::info("Collection #{$event->collectionId} (id {$collection->id}) was updated.");

        CollectionMutatedEvent::safeBroadcast(
            $collection,
            $event->getParams(),
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
