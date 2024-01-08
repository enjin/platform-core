<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionMutated as CollectionMutatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionMutated as CollectionMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class CollectionMutated implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionMutatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection($event->collectionId);
        $attributes = [];
        $royalties = [];

        if ($owner = $event->owner) {
            $attributes['owner_wallet_id'] = WalletService::firstOrStore(['account' => Account::parseAccount($owner)])->id;
        }

        if ($event->royalty === 'SomeMutation') {
            if ($beneficiary = $event->beneficiary) {
                $attributes['royalty_wallet_id'] = WalletService::firstOrStore(['account' => Account::parseAccount($beneficiary)])->id;
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

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        CollectionMutatedEvent::safeBroadcast(
            $collection,
            $event->getParams(),
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
