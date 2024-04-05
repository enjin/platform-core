<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Carbon\Carbon;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated as CollectionCreatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CollectionCreated implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        ray($event);
        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        ray($extrinsic);

        $collection = $this->parseCollection($extrinsic, $event);

        $daemonTransaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        if ($daemonTransaction) {
            Log::info(sprintf('Collection %s (id: %s) was created from transaction %s (id: %s).', $event->collectionId, $collection->id, $daemonTransaction->transaction_chain_hash, $daemonTransaction->id));
        } else {
            Log::info(sprintf('Collection %s (id: %s) was created from an unknown transaction.', $event->collectionId, $collection->id));
        }

        CollectionCreatedEvent::safeBroadcast(
            $collection,
            $daemonTransaction
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
            $calls = collect($calls)->filter(
                fn ($call) => Arr::get($call, 'MultiTokens.create_collection') !== null
            )->first();

            $params = Arr::get($calls, 'MultiTokens.create_collection');
        }
        // TODO: Check fuel tank creation
        // For fuel tanks we have params.call
        //            else {
        //                $params = Arr::get($params, 'call.MultiTokens.create_collection');
        //            }

        ray($params);

        $owner = WalletService::firstOrStore(['account' => Account::parseAccount($event->owner)]);

        $beneficiary = Arr::get($params, 'descriptor.policy.market.royalty.Some.beneficiary');
        $percentage = Arr::get($params, 'descriptor.policy.market.royalty.Some.percentage');

        $collection = Collection::create([
            'collection_chain_id' => $event->collectionId,
            'owner_wallet_id' => $owner->id,
            'max_token_count' => Arr::get($params, 'descriptor.policy.mint.max_token_count.Some'),
            'max_token_supply' => Arr::get($params, 'descriptor.policy.mint.max_token_supply.Some'),
            'force_single_mint' => Arr::get($params, 'descriptor.policy.mint.force_single_mint', false),
            'is_frozen' => false,
            'royalty_wallet_id' => $beneficiary ? WalletService::firstOrStore(['account' => Account::parseAccount($beneficiary)])->id : null,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'token_count' => '0',
            'attribute_count' => '0',
            'total_deposit' => '25000000000000000000',
            'network' => config('enjin-platform.chains.network'),
        ]);

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
