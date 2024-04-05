<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenThawed;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Thawed as ThawedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Thawed extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ThawedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        $collection = $this->getCollection($event->collectionId);
        match (FreezeType::from($event->freezeType)) {
            FreezeType::COLLECTION => $this->thawCollection($collection, $transaction),
            FreezeType::TOKEN => $this->thawToken($collection, $event->tokenId, $transaction),
            FreezeType::COLLECTION_ACCOUNT => $this->thawCollectionAccount($collection, Account::parseAccount($event->account), $transaction),
            FreezeType::TOKEN_ACCOUNT => $this->thawTokenAccount($collection, $event->tokenId, Account::parseAccount($event->account), $transaction),
        };
    }

    /**
     * Thaw collection.
     *
     * @param  mixed  $collection
     */
    protected function thawCollection($collection, $transaciton = null): void
    {
        $collection->is_frozen = false;
        $collection->save();

        Log::info(
            sprintf(
                'Collection #%s (id %s) was thawed.',
                $collection->collection_chain_id,
                $collection->id,
            )
        );

        CollectionThawed::safeBroadcast(
            $collection,
            $transaciton
        );
    }

    /**
     * Thaw token.
     *
     * @param  mixed  $collection
     */
    protected function thawToken($collection, string $tokenChainId, ?Model $transaction = null): void
    {
        $tokenStored = $this->getToken($collection->id, $tokenChainId);
        $tokenStored->is_frozen = false;
        $tokenStored->save();

        Log::info(
            sprintf(
                'Token #%s (id %s) of Collection #%s (id %s) was thawed.',
                $tokenChainId,
                $tokenStored->id,
                $collection->collection_chain_id,
                $collection->id,
            )
        );

        TokenThawed::safeBroadcast(
            $tokenStored,
            $transaction
        );
    }

    /**
     * Thaw collection account.
     *
     * @param  mixed  $collection
     */
    protected function thawCollectionAccount($collection, string $wallet, ?Model $transaction = null): void
    {
        $walletStored = $this->getWallet($wallet);
        $collectionAccountStored = $this->getCollectionAccount($collection->id, $walletStored->id);
        $collectionAccountStored->is_frozen = false;
        $collectionAccountStored->save();

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s (id %s) was thawed.',
                $collectionAccountStored->id,
                $collection->collection_chain_id,
                $collection->id,
                $wallet,
                $walletStored->id,
            )
        );

        CollectionAccountThawed::safeBroadcast(
            $collectionAccountStored,
            $transaction
        );
    }

    /**
     * Thaw token account.
     *
     * @param  mixed  $collection
     */
    protected function thawTokenAccount($collection, string $tokenChainId, string $wallet, ?Model $transaction = null): void
    {
        $walletStored = $this->getWallet($wallet);
        $tokenStored = $this->getToken($collection->id, $tokenChainId);
        $tokenAccountStored = $this->getTokenAccount($collection->id, $tokenStored->id, $walletStored->id);
        $tokenAccountStored->is_frozen = false;
        $tokenAccountStored->save();

        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s (id %s) was thawed.',
                $tokenAccountStored->id,
                $collection->collection_chain_id,
                $collection->id,
                $tokenChainId,
                $tokenStored->id,
                $wallet,
                $walletStored->id,
            )
        );

        TokenAccountThawed::safeBroadcast(
            $tokenAccountStored,
            $transaction
        );
    }
}
