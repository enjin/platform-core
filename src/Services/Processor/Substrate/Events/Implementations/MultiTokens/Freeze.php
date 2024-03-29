<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenFrozen;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Frozen as FrozenPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Freeze implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof FrozenPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        $collection = $this->getCollection($event->collectionId);
        match (FreezeType::from($event->freezeType)) {
            FreezeType::COLLECTION => $this->freezeCollection($collection, $transaction),
            FreezeType::TOKEN => $this->freezeToken($collection, $event->tokenId, $transaction),
            FreezeType::COLLECTION_ACCOUNT => $this->freezeCollectionAccount($collection, Account::parseAccount($event->account), $transaction),
            FreezeType::TOKEN_ACCOUNT => $this->freezeTokenAccount($collection, $event->tokenId, Account::parseAccount($event->account), $transaction),
        };
    }

    /**
     * Freeze collection.
     *
     * @param  mixed  $collection
     */
    protected function freezeCollection($collection, ?Model $transaction = null): void
    {
        $collection->is_frozen = true;
        $collection->save();

        Log::info(
            sprintf(
                'Collection #%s (id %s) was frozen.',
                $collection->collection_chain_id,
                $collection->id,
            )
        );

        CollectionFrozen::safeBroadcast(
            $collection,
            $transaction
        );
    }

    /**
     * Freeze token.
     *
     * @param  mixed  $collection
     */
    protected function freezeToken($collection, string $tokenChainId, ?Model $transaction = null): void
    {
        $tokenStored = $this->getToken($collection->id, $tokenChainId);
        $tokenStored->is_frozen = true;
        $tokenStored->save();

        Log::info(
            sprintf(
                'Token #%s (id %s) of Collection #%s (id %s) was frozen.',
                $tokenChainId,
                $tokenStored->id,
                $collection->collection_chain_id,
                $collection->id,
            )
        );

        TokenFrozen::safeBroadcast(
            $tokenStored,
            $transaction
        );
    }

    /**
     * Freeze collection account.
     *
     * @param  mixed  $collection
     */
    protected function freezeCollectionAccount($collection, string $wallet, ?Model $transaction = null): void
    {
        $walletStored = $this->getWallet($wallet);
        $collectionAccountStored = $this->getCollectionAccount($collection->id, $walletStored->id);
        $collectionAccountStored->is_frozen = true;
        $collectionAccountStored->save();

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s (id %s) was frozen.',
                $collectionAccountStored->id,
                $collection->collection_chain_id,
                $collection->id,
                $wallet,
                $walletStored->id,
            )
        );

        CollectionAccountFrozen::safeBroadcast(
            $collectionAccountStored,
            $transaction
        );
    }

    /**
     * Freeze token account.
     *
     * @param  mixed  $collection
     */
    protected function freezeTokenAccount($collection, string $tokenChainId, string $wallet, ?Model $transaction = null): void
    {
        $walletStored = $this->getWallet($wallet);
        $tokenStored = $this->getToken($collection->id, $tokenChainId);
        $tokenAccountStored = $this->getTokenAccount($collection->id, $tokenStored->id, $walletStored->id);
        $tokenAccountStored->is_frozen = true;
        $tokenAccountStored->save();

        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s (id %s) was frozen.',
                $tokenAccountStored->id,
                $collection->collection_chain_id,
                $collection->id,
                $tokenChainId,
                $tokenStored->id,
                $wallet,
                $walletStored->id,
            )
        );

        TokenAccountFrozen::safeBroadcast(
            $tokenAccountStored,
            $transaction
        );
    }
}
