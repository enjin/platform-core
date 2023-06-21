<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountDestroyed as CollectionAccountDestroyedEvent;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionAccountDestroyed as CollectionAccountDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class CollectionAccountDestroyed implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionAccountDestroyedPolkadart) {
            return;
        }

        $collection = $this->getCollection($event->collectionId);
        $account = WalletService::firstOrStore(['account' => $event->account]);
        $collectionAccount = CollectionAccount::firstWhere([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
        ]);
        $collectionAccount->delete();

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s was deleted.',
                $collectionAccount->id,
                $event->collectionId,
                $collection->id,
                $account->address
            )
        );

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        CollectionAccountDestroyedEvent::safeBroadcast(
            $this->getCollection($event->collectionId),
            $account,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
