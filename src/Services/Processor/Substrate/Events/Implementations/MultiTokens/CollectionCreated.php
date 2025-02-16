<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Carbon\Carbon;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated as CollectionCreatedEvent;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollectionCreated extends SubstrateEvent
{
    /** @var CollectionCreatedPolkadart */
    protected Event $event;
    protected ?Collection $collectionCreated = null;

    public function run(): void
    {
        $this->collectionCreatedCountAtBlock($this->block->number);

        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        $extrinsic = $this->block->extrinsics[$this->event->extrinsicIndex];
        $count = Cache::get(PlatformCache::BLOCK_EVENT_COUNT->key("collectionCreated:block:{$this->block->number}"));

        $this->parseCollection($extrinsic, $this->event, $count - 1);
        Cache::forget(PlatformCache::BLOCK_EVENT_COUNT->key("collectionCreated:block:{$this->block->number}"));
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'Collection %s was created from transaction %s.',
                $this->event->collectionId,
                $this->block->extrinsics[$this->event->extrinsicIndex]?->hash,
            )
        );
    }

    public function broadcast(): void
    {
        CollectionCreatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
            $this->collectionCreated,
        );
    }

    protected function collectionCreatedCountAtBlock(string $block): void
    {
        $key = PlatformCache::BLOCK_EVENT_COUNT->key("collectionCreated:block:{$block}");
        Cache::add($key, 0, now()->addMinute());
        Cache::increment($key);
    }

    protected function parseCollection(Extrinsic $extrinsic, CollectionCreatedPolkadart $event, int $count = 0): void
    {
        $params = $extrinsic->params;

        // This unwraps any calls from a FuelTank extrinsic
        if ($extrinsic->module === 'FuelTanks') {
            $params = $this->getValue($params, ['call.MultiTokens.create_collection', 'call.MatrixUtility.batch', 'call.Utility.batch', 'call.Utility.batch_all']);
        }

        // This is used for CollectionCreated events generated on matrixUtility.batch or utility.batch extrinsics
        if (($calls = Arr::get($params, 'calls')) !== null) {
            $calls = collect($calls)->filter(
                fn ($call) => Arr::get($call, 'MultiTokens.create_collection') !== null
            )->values();

            $params = Arr::get($calls, "{$count}.MultiTokens.create_collection");
        }

        $owner = $this->firstOrStoreAccount($event->owner);

        if (currentSpec() >= 1020) {
            // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
            $beneficiary = $this->getValue($params, ['descriptor.policy.market.royalty.0.beneficiary', 'descriptor.policy.market.beneficiary']);
            $percentage = $this->getValue($params, ['descriptor.policy.market.royalty.0.percentage', 'descriptor.policy.market.percentage']);
        } else {
            $beneficiary = $this->getValue($params, ['descriptor.policy.market.royalty.Some.beneficiary', 'descriptor.policy.market.beneficiary']);
            $percentage = $this->getValue($params, ['descriptor.policy.market.royalty.Some.percentage', 'descriptor.policy.market.percentage']);
        }

        $this->collectionCreated = Collection::updateOrCreate([
            'collection_chain_id' => $event->collectionId,
        ], [
            'owner_wallet_id' => $owner->id,
            'max_token_count' => $this->getValue($params, ['descriptor.policy.mint.max_token_count.Some', 'descriptor.policy.mint.max_token_count']),
            'max_token_supply' => $this->getValue($params, ['descriptor.policy.mint.max_token_supply.Some', 'descriptor.policy.mint.max_token_supply']),
            'force_collapsing_supply' => $this->getValue($params, ['descriptor.policy.mint.force_collapsing_supply']) ?? false,
            'is_frozen' => false,
            'royalty_wallet_id' => $beneficiary ? WalletService::firstOrStore(['account' => Account::parseAccount($beneficiary)])->id : null,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'token_count' => '0',
            'attribute_count' => '0',
            'total_deposit' => '25000000000000000000',
            // 'depositor' => null, TODO: We have a depositor which is an Option<AccountId32> here
            'network' => network()->name,
        ]);

        $this->syncableService->updateOrInsert($this->collectionCreated->collection_chain_id, ModelType::COLLECTION);

        $this->collectionRoyaltyCurrencies($this->collectionCreated->id, Arr::get($params, 'descriptor.explicit_royalty_currencies'));
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
