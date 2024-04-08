<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenCreated as TokenCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenCreated as TokenCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens\Mint;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class TokenCreated extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenCreatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        ray($event);

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $token = $this->parseToken($extrinsic, $event);

        TokenCreatedEvent::safeBroadcast(
            $token,
            $this->firstOrStoreAccount($event->issuer),
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }

    /**
     * @throws PlatformException
     */
    public function parseToken(Extrinsic $extrinsic, TokenCreatedPolkadart $event): mixed
    {
        ray($extrinsic);
        ray($event);

        // Fails if collection is not found
        $collection = $this->getCollection($event->collectionId);
        $createToken = Arr::get($extrinsic->params, 'params.CreateToken');

        if ($extrinsic->call === 'batch_mint') {
            $recipient = collect(Arr::get($extrinsic->params, 'recipients'))->firstWhere('params.CreateToken.token_id', $event->tokenId);
            $createToken = Arr::get($recipient, 'params.CreateToken');
        }

        ray($createToken);

        if ($extrinsic->module !== 'MultiTokens') {
            throw new \Exception('Module not found');
        }

        //        if ($extrinsic instanceof Extrinsic) {
        //            // TODO: Batch extrinsics - We need to pop the call from the extrinsic
        //            // For batch we have params.calls
        //            if (($calls = Arr::get($extrinsic->params, 'calls')) !== null) {
        //                $call = collect($calls)
        //                    ->filter(
        //                        fn ($item) => (Arr::get($item, 'MultiTokens.mint.collection_id') === $event->collectionId
        //                                && Arr::get($item, 'MultiTokens.mint.params.CreateToken.token_id') === $event->tokenId)
        //                            || (Arr::get($item, 'MultiTokens.force_mint.collection_id') === $event->collectionId
        //                                && Arr::get($item, 'MultiTokens.force_mint.params.CreateOrMint.token_id') === $event->tokenId)
        //                    )->first();
        //
        //                $createToken = Arr::get($call, 'MultiTokens.mint.params.CreateToken') ?? Arr::get($call, 'MultiTokens.force_mint.params.CreateOrMint');
        //            }
        //            // For fuel tanks we have params.call
        //            else {
        //                $createToken = Arr::get($extrinsic->params, 'call.MultiTokens.mint.params.CreateToken');
        //            }
        //        }

        $isSingleMint = Arr::get($createToken, 'cap.Some') === 'SingleMint' || Arr::has($createToken, 'cap.SingleMint');
        $capSupply = $this->getValue($createToken, ['cap.Some.Supply', 'cap.Supply']);

        if (Arr::get($createToken, 'cap') !== null && $capSupply === null && !$isSingleMint) {
            ray($isSingleMint);
            ray($capSupply);

            throw new \Exception('Cap not found');
        }


        $cap = TokenMintCapType::INFINITE;

        if ($capSupply !== null) {
            $cap = TokenMintCapType::SUPPLY;
        } elseif ($isSingleMint) {
            $cap = TokenMintCapType::SINGLE_MINT;
        }

        $beneficiary = $this->firstOrStoreAccount(Account::parseAccount($this->getValue($createToken, ['behavior.Some.HasRoyalty.beneficiary', 'behavior.HasRoyalty.beneficiary'])));
        $percentage = $this->getValue($createToken, ['behavior.Some.HasRoyalty.percentage', 'behavior.HasRoyalty.percentage']);
        $isCurrency = Arr::get($createToken, 'behavior.Some') === 'IsCurrency' || Arr::has($createToken, 'behavior.IsCurrency');

        $unitPrice = gmp_init($this->getValue($createToken, ['sufficiency.Insufficient.unit_price.Some', 'sufficiency.Insufficient']) ?? 10 ** 16);
        $minBalance = Arr::get($createToken, 'sufficiency.Sufficient.minimum_balance');
        if ($minBalance || Arr::has($createToken, 'sufficiency.Sufficient')) {
            throw new \Exception('Minimum balance not found');
        }

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
            'cap_supply' => $capSupply,
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
}
