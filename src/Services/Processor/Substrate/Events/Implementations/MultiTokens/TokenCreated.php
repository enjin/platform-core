<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenCreated as TokenCreatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenCreated as TokenCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Generic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens\BatchMint;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens\Mint;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;

class TokenCreated implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenCreatedPolkadart) {
            return;
        }

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $token = $this->parseToken($extrinsic, $event);

        TokenCreatedEvent::safeBroadcast(
            $token,
            WalletService::firstOrStore(['account' => Account::parseAccount($event->issuer)]),
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }

    public function parseToken(Mint|BatchMint|Generic $extrinsic, TokenCreatedPolkadart $event): mixed
    {
        $params = Arr::get($extrinsic->params, 'params.CreateToken');

        if ($extrinsic instanceof BatchMint) {
            $recipient = collect(Arr::get($extrinsic->params, 'recipients'))->firstWhere('params.CreateToken.token_id', $event->tokenId);
            $params = Arr::get($recipient, 'params.CreateToken');
        }

        if ($extrinsic instanceof Generic) {
            $calls = collect(Arr::get($extrinsic->params, 'calls'))
                ->filter(
                    fn ($call) => Arr::get($call, 'MultiTokens.mint.collection_id') === $event->collectionId
                    && Arr::get($call, 'MultiTokens.mint.params.CreateToken.token_id') === $event->tokenId
                )->first();

            $params = Arr::get($calls, 'MultiTokens.mint.params.CreateToken');
        }

        $collection = $this->getCollection($event->collectionId);
        $isSingleMint = Arr::get($params, 'cap.Some') === 'SingleMint';
        $capSupply = Arr::get($params, 'cap.Some.Supply');
        $cap = TokenMintCapType::INFINITE;

        if ($capSupply !== null) {
            $cap = TokenMintCapType::SUPPLY;
        } elseif ($isSingleMint) {
            $cap = TokenMintCapType::SINGLE_MINT;
        }

        $beneficiary = Arr::get($params, 'behavior.Some.HasRoyalty.beneficiary');
        $percentage = Arr::get($params, 'behavior.Some.HasRoyalty.percentage');
        $isCurrency = Arr::get($params, 'behavior.Some') === 'IsCurrency';

        $unitPrice = gmp_init(Arr::get($params, 'unit_price') ?? Arr::get($params, 'sufficiency.Insufficient.unit_price.Some') ?? 10 ** 16);
        $minBalance = Arr::get($params, 'sufficiency.Sufficient.minimum_balance');

        if (!$minBalance) {
            $minBalance = gmp_div(gmp_pow(10, 16), $unitPrice, GMP_ROUND_PLUSINF);
            $minBalance = gmp_cmp(1, $minBalance) > 0 ? '1' : gmp_strval($minBalance);
        }

        $isFrozen = in_array(Arr::get($params, 'freeze_state.Some'), ['Permanent', 'Temporary']);

        return Token::create([
            'collection_id' => $collection->id,
            'token_chain_id' => $event->tokenId,
            'supply' => $initialSupply = Arr::get($params, 'initial_supply'),
            'cap' => $cap->name,
            'cap_supply' => $capSupply,
            'is_frozen' => $isFrozen,
            'royalty_wallet_id' => $beneficiary ? WalletService::firstOrStore(['account' => Account::parseAccount($beneficiary)])->id : null,
            'royalty_percentage' => $percentage ? $percentage / 10 ** 7 : null,
            'is_currency' => $isCurrency,
            'listing_forbidden' => Arr::get($params, 'listing_forbidden'),
            'unit_price' => gmp_strval($unitPrice),
            'minimum_balance' => $minBalance,
            'mint_deposit' => $initialSupply * $unitPrice,
            'attribute_count' => 0,
        ]);
    }
}
