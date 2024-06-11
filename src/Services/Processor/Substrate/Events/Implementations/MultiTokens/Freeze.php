<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenFrozen;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Frozen as FrozenPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Freeze extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof FrozenPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $transaction = $this->getTransaction($block, $event->extrinsicIndex);

        match (FreezeType::from($event->freezeType)) {
            FreezeType::COLLECTION => $this->freezeCollection($collection, $transaction),
            FreezeType::TOKEN => $this->freezeToken($collection, $event->tokenId, $transaction),
            FreezeType::COLLECTION_ACCOUNT => $this->freezeCollectionAccount($collection, $event->account, $transaction),
            FreezeType::TOKEN_ACCOUNT => $this->freezeTokenAccount($collection, $event->tokenId, $event->account, $transaction),
        };
    }

    protected function freezeCollection(Collection $collection, ?Model $transaction = null): void
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
     * @throws PlatformException
     */
    protected function freezeToken(Collection $collection, string $tokenId, ?Model $transaction = null): void
    {
        // Fails if it doesn't find the token
        $tokenStored = $this->getToken($collection->id, $tokenId);
        $tokenStored->is_frozen = true;
        $tokenStored->save();

        Log::info(
            sprintf(
                'Token #%s (id %s) of Collection #%s (id %s) was frozen.',
                $tokenId,
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

    protected function freezeCollectionAccount(Collection $collection, string $account, ?Model $transaction = null): void
    {
        $owner = $this->firstOrStoreAccount($account);
        $collectionAccount = $this->getCollectionAccount($collection->id, $owner->id);
        $collectionAccount->is_frozen = true;
        $collectionAccount->save();

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s (id %s) was frozen.',
                $collectionAccount->id,
                $collection->collection_chain_id,
                $collection->id,
                $owner,
                $account->id ?? 'unknown',
            )
        );

        CollectionAccountFrozen::safeBroadcast(
            $collectionAccount,
            $transaction
        );
    }

    /**
     * @throws PlatformException
     */
    protected function freezeTokenAccount(Collection $collection, string $tokenId, string $account, ?Model $transaction = null): void
    {
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $tokenId);
        $owner = $this->firstOrStoreAccount($account);

        $tokenAccount = $this->getTokenAccount($collection->id, $token->id, $owner->id);
        $tokenAccount->is_frozen = true;
        $tokenAccount->save();

        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s (id %s) was frozen.',
                $tokenAccount->id,
                $collection->collection_chain_id,
                $collection->id,
                $tokenId,
                $token->id,
                $account,
                $owner->id,
            )
        );

        TokenAccountFrozen::safeBroadcast(
            $tokenAccount,
            $transaction
        );
    }
}
