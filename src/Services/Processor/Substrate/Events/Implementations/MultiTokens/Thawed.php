<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenThawed;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Thawed as ThawedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Thawed extends SubstrateEvent
{
    /** @var ThawedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$event instanceof ThawedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $transaction = $this->getTransaction($block, $event->extrinsicIndex);

        match (FreezeType::from($event->freezeType)) {
            FreezeType::COLLECTION => $this->thawCollection($collection, $transaction),
            FreezeType::TOKEN => $this->thawToken($collection, $event->tokenId, $transaction),
            FreezeType::COLLECTION_ACCOUNT => $this->thawCollectionAccount($collection, $event->account, $transaction),
            FreezeType::TOKEN_ACCOUNT => $this->thawTokenAccount($collection, $event->tokenId, $event->account, $transaction),
        };
    }

    public function log()
    {
        // TODO: Implement log() method.
    }

    public function broadcast()
    {
        // TODO: Implement broadcast() method.
    }

    protected function thawCollection(Collection $collection, ?Model $transaction = null): void
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
            $transaction
        );
    }

    /**
     * @throws PlatformException
     */
    protected function thawToken(Collection $collection, string $tokenId, ?Model $transaction = null): void
    {
        // Fails if it doesn't find the token
        $tokenStored = $this->getToken($collection->id, $tokenId);
        $tokenStored->is_frozen = false;
        $tokenStored->save();

        Log::info(
            sprintf(
                'Token #%s (id %s) of Collection #%s (id %s) was thawed.',
                $tokenId,
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

    protected function thawCollectionAccount(Collection $collection, string $account, ?Model $transaction = null): void
    {
        $owner = $this->firstOrStoreAccount($account);
        $collectionAccount = $this->getCollectionAccount($collection->id, $owner->id);
        $collectionAccount->is_frozen = false;
        $collectionAccount->save();

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s (id %s) was thawed.',
                $collectionAccount->id,
                $collection->collection_chain_id,
                $collection->id,
                $owner,
                $account->id ?? 'unknown',
            )
        );

        CollectionAccountThawed::safeBroadcast(
            $collectionAccount,
            $transaction
        );
    }

    /**
     * @throws PlatformException
     */
    protected function thawTokenAccount(Collection $collection, string $tokenId, string $account, ?Model $transaction = null): void
    {
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $tokenId);
        $owner = $this->firstOrStoreAccount($account);

        $tokenAccount = $this->getTokenAccount($collection->id, $token->id, $owner->id);
        $tokenAccount->is_frozen = false;
        $tokenAccount->save();

        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s (id %s) was thawed.',
                $tokenAccount->id,
                $collection->collection_chain_id,
                $collection->id,
                $tokenId,
                $token->id,
                $account,
                $owner->id,
            )
        );

        TokenAccountThawed::safeBroadcast(
            $tokenAccount,
            $transaction
        );
    }
}
