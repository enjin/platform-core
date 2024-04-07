<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Carbon\Carbon;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated as CollectionCreatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CollectionCreated extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof CollectionCreatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $collection = $this->parseCollection($extrinsic, $event);
        $transaction = $this->getTransaction($block, $event->extrinsicIndex);

        Log::info(sprintf('Collection %s (id: %s) was created from transaction %s (id: %s).', $event->collectionId, $collection->id, $transaction?->transaction_chain_hash ?? 'unknown', $transaction?->id ?? 'unknown'));

        CollectionCreatedEvent::safeBroadcast(
            $collection,
            $transaction,
        );
    }

    protected function parseCollection(Extrinsic $extrinsic, CollectionCreatedPolkadart $event): Collection
    {
        ray($extrinsic);
        $params = $extrinsic->params ?? [];
        ray($params);

        // TODO: Batch extrinsics - We need to pop the call from the extrinsic
        // For batch we have params.calls
        if (($calls = Arr::get($params, 'calls')) !== null) {
            throw new \Exception('Batch extrinsics not supported yet');
            $calls = collect($calls)->filter(
                fn ($call) => Arr::get($call, 'MultiTokens.create_collection') !== null
            )->first();

            $params = Arr::get($calls, 'MultiTokens.create_collection');
        }

        if (Arr::get($params, 'call.MultiTokens.create_collection')) {
            throw new \Exception('Batch extrinsics not supported yet');
        }

        // TODO: Check fuel tank creation
        // For fuel tanks we have params.call
        //            else {
        //                $params = Arr::get($params, 'call.MultiTokens.create_collection');
        //            }


        $owner = $this->firstOrStoreAccount($event->owner);
        $beneficiary = $this->getValue($params, ['descriptor.policy.market']);
        if ($beneficiary != null) {
            throw new \Exception('Beneficiary not found');
        }

        // Check
        $beneficiary = $this->getValue($params, ['descriptor.policy.market.royalty.Some.beneficiary', 'descriptor.policy.market.royalty']);
        $percentage = $this->getValue($params, ['descriptor.policy.market.royalty.Some.percentage']);

        $collection = Collection::create([
            'collection_chain_id' => $event->collectionId,
            'owner_wallet_id' => $owner->id,
            'max_token_count' => $this->getValue($params, ['descriptor.policy.mint.max_token_count.Some', 'descriptor.policy.mint.max_token_count']),
            'max_token_supply' => $this->getValue($params, ['descriptor.policy.mint.max_token_supply.Some', 'descriptor.policy.mint.max_token_supply']),
            'force_single_mint' => $this->getValue($params, ['descriptor.policy.mint.force_single_mint']) ?? false,
            'is_frozen' => false,
            'royalty_wallet_id' => $beneficiary ? WalletService::firstOrStore(['account' => Account::parseAccount($beneficiary)])->id : null,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'token_count' => '0',
            'attribute_count' => '0',
            'total_deposit' => '25000000000000000000',
            'network' => network(),
        ]);

        if (Arr::get($params, 'descriptor.explicit_royalty_currencies')) {
            throw new \Exception('Royalty currencies not found');
        }

        $this->collectionRoyaltyCurrencies($collection->id, Arr::get($params, 'descriptor.explicit_royalty_currencies'));

        return $collection;
    }

    protected function collectionRoyaltyCurrencies(string $collectionId, array $royaltyCurrencies): void
    {
        foreach ($royaltyCurrencies as $currency) {
            CollectionRoyaltyCurrency::updateOrCreate(
                [
                    'collection_id' => $collectionId,
                    'currency_collection_chain_id' => $currency['collection_id'],
                    'currency_token_chain_id' => $currency['token_id'],
                ],
                [
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ]
            );
        }
    }
}
