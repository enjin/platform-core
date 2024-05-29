<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountThawed;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenThawed;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Thawed as ThawedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
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
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->thawCollection(),
            FreezeType::TOKEN => $this->thawToken(),
            FreezeType::COLLECTION_ACCOUNT => $this->thawCollectionAccount(),
            FreezeType::TOKEN_ACCOUNT => $this->thawTokenAccount(),
        };
    }

    public function log(): void
    {
        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->logCollectionThawed(),
            FreezeType::TOKEN => $this->logTokenThawed(),
            FreezeType::COLLECTION_ACCOUNT => $this->logCollectionAccountThawed(),
            FreezeType::TOKEN_ACCOUNT => $this->logTokenAccountThawed(),
        };
    }

    public function broadcast(): void
    {
        match (FreezeType::from($this->event->freezeType)) {
            FreezeType::COLLECTION => $this->broadcastCollectionThawed(),
            FreezeType::TOKEN => $this->broadcastTokenThawed(),
            FreezeType::COLLECTION_ACCOUNT => $this->broadcastCollectionAccountThawed(),
            FreezeType::TOKEN_ACCOUNT => $this->broadcastTokenAccountThawed(),
        };
    }

    protected function thawCollection(): void
    {
        Collection::where('collection_chain_id', $this->event->collectionId)
            ->update(['is_frozen' => false]);
    }

    /**
     * @throws PlatformException
     */
    protected function thawToken(): void
    {
        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);

        Token::where([
            'collection_id' => $collection->id,
            'token_chain_id' => $this->event->tokenId,
        ])->update(['is_frozen' => false]);
    }

    /**
     * @throws PlatformException
     */
    protected function thawCollectionAccount(): void
    {
        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $owner = $this->firstOrStoreAccount($this->event->account);

        CollectionAccount::where([
            'collection_id' => $collection->id,
            'wallet_id' => $owner->id,
        ])->update(['is_frozen' => false]);
    }

    /**
     * @throws PlatformException
     */
    protected function thawTokenAccount(): void
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
        ])->update(['is_frozen' => false]);
    }

    protected function logCollectionThawed(): void
    {
        Log::info(
            sprintf(
                'Collection %s was thawed.',
                $this->event->collectionId,
            )
        );
    }

    protected function logCollectionAccountThawed(): void
    {
        Log::info(
            sprintf(
                'CollectionAccount of collection %s and account %s was thawed.',
                $this->event->collectionId,
                $this->event->account,
            )
        );
    }

    protected function logTokenThawed(): void
    {
        Log::info(
            sprintf(
                'Token %s of collection %s was thawed.',
                $this->event->tokenId,
                $this->event->collectionId,
            )
        );
    }

    protected function logTokenAccountThawed(): void
    {
        Log::info(
            sprintf(
                'TokenAccount of collection %s, token #%s and account %s was thawed.',
                $this->event->collectionId,
                $this->event->tokenId,
                $this->event->account,
            )
        );
    }

    protected function broadcastCollectionThawed(): void
    {
        CollectionThawed::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }

    protected function broadcastCollectionAccountThawed(): void
    {
        CollectionAccountThawed::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }

    protected function broadcastTokenThawed(): void
    {
        TokenThawed::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }

    protected function broadcastTokenAccountThawed(): void
    {
        TokenAccountThawed::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }
}
