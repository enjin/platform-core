<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenCreated as TokenCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenCreated as TokenCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class TokenCreated extends SubstrateEvent
{
    /** @var TokenCreatedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        $this->tokenCreatedCountAtBlock($this->block->number);

        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        $extrinsic = $this->block->extrinsics[$this->event->extrinsicIndex];
        $count = Cache::get(PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$this->block->number}"));

        $this->parseToken($extrinsic, $this->event, $count - 1);
        Cache::forget(PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$this->block->number}"));
    }

    /**
     * @throws PlatformException
     */
    public function parseToken(Extrinsic $extrinsic, TokenCreatedPolkadart $event, int $count = 0): mixed
    {
        // Fails if collection is not found
        $collection = $this->getCollection($event->collectionId);
        $params = $extrinsic->params;

        // This unwraps any calls from a FuelTank extrinsic
        if ($extrinsic->module === 'FuelTanks') {
            $params = $this->getValue($params, ['call.MultiTokens.mint', 'call.MatrixUtility.batch', 'call.Utility.batch', 'call.Utility.batch_all']);
        }

        // This is used for TokenCreated events generated on matrixUtility.batch or utility.batch extrinsics
        if (($calls = Arr::get($params, 'calls')) !== null) {
            $calls = collect($calls)->filter(
                fn ($call) => Arr::get($call, 'MultiTokens.mint') !== null || Arr::get($call, 'MultiTokens.force_mint') !== null
            )->values();

            $params = Arr::get($calls, "{$count}.MultiTokens.mint") ?? Arr::get($calls, "{$count}.MultiTokens.force_mint");
        }

        // This gets the correct recipient from multiTokens.batch_mint
        if ($extrinsic->call === 'batch_mint' || Arr::get($extrinsic->params, 'call.MultiTokens.batch_mint') !== null) {
            $recipients = $this->getValue($extrinsic->params, ['recipients', 'call.MultiTokens.batch_mint.recipients']);
            $params = collect($recipients)->firstWhere('params.CreateToken.token_id', $event->tokenId);
        }

        $createToken = Arr::get($params, 'params.CreateToken') ?? Arr::get($params, 'params.CreateOrMint');
        $isSingleMint = Arr::get($createToken, 'cap.Some') === 'SingleMint' || Arr::has($createToken, 'cap.SingleMint');
        $capSupply = $this->getValue($createToken, ['cap.Some.Supply', 'cap.Supply']);
        $collapsingSupply = $this->getValue($createToken, ['cap.Some.CollapsingSupply', 'cap.CollapsingSupply']);

        $cap = TokenMintCapType::INFINITE;

        if ($capSupply !== null) {
            $cap = TokenMintCapType::SUPPLY;
        } elseif ($collapsingSupply !== null) {
            $cap = TokenMintCapType::COLLAPSING_SUPPLY;
        } elseif ($isSingleMint) {
            $cap = TokenMintCapType::SINGLE_MINT;
        }

        $beneficiary = $this->firstOrStoreAccount(Account::parseAccount($this->getValue($createToken, ['behavior.Some.HasRoyalty.beneficiary', 'behavior.HasRoyalty.beneficiary'])));
        $percentage = $this->getValue($createToken, ['behavior.Some.HasRoyalty.percentage', 'behavior.HasRoyalty.percentage']);
        $isCurrency = Arr::get($createToken, 'behavior.Some') === 'IsCurrency' || Arr::has($createToken, 'behavior.IsCurrency');

        $unitPrice = gmp_init($this->getValue($createToken, ['sufficiency.Insufficient.unit_price.Some', 'sufficiency.Insufficient']) ?? 10 ** 16);
        $minBalance = Arr::get($createToken, 'sufficiency.Sufficient.minimum_balance');

        if (!$minBalance) {
            $minBalance = gmp_div(gmp_pow(10, 16), $unitPrice, GMP_ROUND_PLUSINF);
            $minBalance = gmp_cmp(1, $minBalance) > 0 ? '1' : gmp_strval($minBalance);
        }

        $isFrozen = in_array($this->getValue($createToken, ['freeze_state.Some', 'freeze_state']), ['Permanent', 'Temporary']);

        return Token::create([
            'collection_id' => $collection->id,
            'token_chain_id' => $event->tokenId,
            'supply' => $this->getValue($createToken, ['initial_supply', 'amount']) ?? 0,
            'cap' => $cap->name,
            'cap_supply' => $capSupply ?? $collapsingSupply,
            'is_frozen' => $isFrozen,
            'royalty_wallet_id' => $beneficiary?->id,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'is_currency' => $isCurrency,
            'listing_forbidden' => Arr::get($createToken, 'listing_forbidden') ?? false,
            'unit_price' => gmp_strval($unitPrice),
            'minimum_balance' => $minBalance,
            'attribute_count' => 0,
        ]);
    }

    public function log(): void
    {
        // TODO: Implement log() method.
    }

    public function broadcast(): void
    {
        TokenCreatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }

    protected function tokenCreatedCountAtBlock(string $block): void
    {
        $key = PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$block}");
        Cache::add($key, 0, now()->addMinute());
        Cache::increment($key);
    }
}
