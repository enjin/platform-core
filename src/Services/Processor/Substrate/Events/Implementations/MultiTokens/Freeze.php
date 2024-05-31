<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountFrozen;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenFrozen;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Frozen as FrozenPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Freeze extends SubstrateEvent
{
    /** @var FrozenPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->freezeCollection(),
            FreezeType::TOKEN => $this->freezeToken(),
            FreezeType::COLLECTION_ACCOUNT => $this->freezeCollectionAccount(),
            FreezeType::TOKEN_ACCOUNT => $this->freezeTokenAccount(),
        };
    }

    public function log(): void
    {
        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->logCollectionFrozen(),
            FreezeType::TOKEN => $this->logTokenFrozen(),
            FreezeType::COLLECTION_ACCOUNT => $this->logCollectionAccountFrozen(),
            FreezeType::TOKEN_ACCOUNT => $this->logTokenAccountFrozen(),
        };
    }

    public function broadcast(): void
    {
        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->broadcastCollectionFrozen(),
            FreezeType::TOKEN => $this->broadcastTokenFrozen(),
            FreezeType::COLLECTION_ACCOUNT => $this->broadcastCollectionAccountFrozen(),
            FreezeType::TOKEN_ACCOUNT => $this->broadcastTokenAccountFrozen(),
        };
    }

    protected function freezeCollection(): void
    {
        //         $this->extra = ['collection_owner' => $collection->owner->public_key];
        Collection::where('collection_chain_id', $this->event->collectionId)
            ->update(['is_frozen' => true]);
    }

    /**
     * @throws PlatformException
     */
    protected function freezeToken(): void
    {
        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);

        Token::where([
            'collection_id' => $collection->id,
            'token_chain_id' => $this->event->tokenId,
        ])->update(['is_frozen' => true]);
    }

    /**
     * @throws PlatformException
     */
    protected function freezeCollectionAccount(): void
    {
        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $owner = $this->firstOrStoreAccount($this->event->account);

        CollectionAccount::where([
            'collection_id' => $collection->id,
            'wallet_id' => $owner->id,
        ])->update(['is_frozen' => true]);
    }

    /**
     * @throws PlatformException
     */
    protected function freezeTokenAccount(): void
    {
        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $this->event->tokenId);
        $owner = $this->firstOrStoreAccount($this->event->account);

        TokenAccount::where([
            'collection_id' => $collection->id,
            'token_id' => $token->id,
            'wallet_id' => $owner->id,
        ])->update(['is_frozen' => true]);
    }

    protected function logCollectionFrozen(): void
    {
        Log::info(
            sprintf(
                'Collection %s was frozen.',
                $this->event->collectionId,
            )
        );
    }

    protected function logCollectionAccountFrozen(): void
    {
        Log::info(
            sprintf(
                'CollectionAccount of collection %s and account %s was frozen.',
                $this->event->collectionId,
                $this->event->account,
            )
        );
    }

    protected function logTokenFrozen(): void
    {
        Log::info(
            sprintf(
                'Token %s of collection %s was frozen.',
                $this->event->tokenId,
                $this->event->collectionId,
            )
        );
    }

    protected function logTokenAccountFrozen(): void
    {
        Log::info(
            sprintf(
                'TokenAccount of collection %s, token #%s and account %s was frozen.',
                $this->event->collectionId,
                $this->event->tokenId,
                $this->event->account,
            )
        );
    }

    protected function broadcastCollectionFrozen(): void
    {
        CollectionFrozen::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }

    protected function broadcastCollectionAccountFrozen(): void
    {
        CollectionAccountFrozen::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }

    protected function broadcastTokenFrozen(): void
    {
        TokenFrozen::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }

    protected function broadcastTokenAccountFrozen(): void
    {
        TokenAccountFrozen::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
