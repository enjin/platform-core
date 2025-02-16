<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenCreated as TokenCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenCreated as TokenCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenCreated extends SubstrateEvent
{
    /** @var TokenCreatedPolkadart */
    protected Event $event;
    protected ?Token $tokenCreated = null;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        $this->tokenCreatedCountAtBlock($this->block->number);

        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        $count = Cache::get(PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$this->block->number}"));
        $this->tokenCreated = $this->parseToken($this->event, $count - 1);
        Cache::forget(PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$this->block->number}"));
    }

    /**
     * @throws PlatformException
     */
    public function parseToken(TokenCreatedPolkadart $event, int $count = 0): mixed
    {
        // Fails if collection is not found
        $collection = $this->getCollection($event->collectionId);
        $this->extra = ['collection_owner' => $collection->owner->public_key];

        $extrinsic = $this->block->extrinsics[$this->event->extrinsicIndex];
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
        $capSupply = $this->getValue($createToken, ['cap.Some.Supply', 'cap.Supply']);
        $collapsingSupply = $this->getValue($createToken, ['cap.Some.CollapsingSupply', 'cap.CollapsingSupply']);

        $cap = null;

        if ($capSupply !== null) {
            $cap = TokenMintCapType::SUPPLY;
        } elseif ($collapsingSupply !== null) {
            $cap = TokenMintCapType::COLLAPSING_SUPPLY;
        }

        if (currentSpec() >= 1020) {
            // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
            $beneficiary = $this->firstOrStoreAccount(Account::parseAccount($this->getValue($createToken, ['behavior.HasRoyalty.0.beneficiary', 'behavior.HasRoyalty.0.beneficiary'])));
            $percentage = $this->getValue($createToken, ['behavior.Some.HasRoyalty.0.percentage', 'behavior.HasRoyalty.0.percentage']);
        } else {
            $beneficiary = $this->firstOrStoreAccount(Account::parseAccount($this->getValue($createToken, ['behavior.Some.HasRoyalty.beneficiary', 'behavior.HasRoyalty.beneficiary'])));
            $percentage = $this->getValue($createToken, ['behavior.Some.HasRoyalty.percentage', 'behavior.HasRoyalty.percentage']);
        }

        $isCurrency = Arr::get($createToken, 'behavior.Some') === 'IsCurrency' || Arr::has($createToken, 'behavior.IsCurrency');
        $isFrozen = in_array($this->getValue($createToken, ['freeze_state.Some', 'freeze_state']), ['Permanent', 'Temporary']);
        $name = is_array($name = $this->getValue($createToken, 'metadata.name')) ? HexConverter::bytesToHexPrefixed($name) : $name;
        $symbol = is_array($symbol = $this->getValue($createToken, 'metadata.symbol')) ? HexConverter::bytesToHexPrefixed($symbol) : $symbol;
        $privilegedParams = $this->getValue($createToken, 'privileged_params');

        return Token::create([
            'collection_id' => $collection->id,
            'token_chain_id' => $event->tokenId,
            'supply' => $this->getValue($createToken, ['initial_supply', 'amount']) ?? 0,
            'cap' => $cap?->name,
            'cap_supply' => $capSupply ?? $collapsingSupply,
            'is_frozen' => $isFrozen,
            'royalty_wallet_id' => $beneficiary?->id,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'is_currency' => $isCurrency,
            'listing_forbidden' => Arr::get($createToken, 'listing_forbidden') ?? false,
            'requires_deposit' => $privilegedParams === null ? true : Arr::get($privilegedParams, 'requires_deposit', true),
            'creation_depositor' => null,
            'creation_deposit_amount' => 0, // TODO: Implement this
            'owner_deposit' => 0, // TODO: Implement this
            'total_token_account_deposit' => 0, // TODO: Implement this
            'attribute_count' => 0, // This will be increased in the AttributeSet event
            'account_count' => $this->getValue($createToken, 'account_deposit_count') ?? 1,
            'infusion' => $this->getValue($createToken, 'infusion') ?? 0,
            'anyone_can_infuse' => Arr::get($createToken, 'anyone_can_infuse') ?? false,
            'decimal_count' => $this->getValue($createToken, 'metadata.decimal_count') ?? 0,
            'name' => $name === '0x' ? null : $name,
            'symbol' => $symbol === '0x' ? null : $symbol,
        ]);
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Account %s created token %s-%s.',
            $this->event->issuer,
            $this->event->collectionId,
            $this->event->tokenId,
        ));
    }

    public function broadcast(): void
    {
        TokenCreatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
            $this->tokenCreated,
        );
    }

    protected function tokenCreatedCountAtBlock(string $block): void
    {
        $key = PlatformCache::BLOCK_EVENT_COUNT->key("tokenCreated:block:{$block}");
        Cache::add($key, 0, now()->addMinute());
        Cache::increment($key);
    }
}
