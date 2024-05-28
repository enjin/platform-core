<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionApproved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenApproved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Approved as ApprovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Approved extends SubstrateEvent
{
    /** @var ApprovedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $operator = $this->firstOrStoreAccount($this->event->operator);
        $owner =  $this->firstOrStoreAccount($this->event->owner);

        if (is_null($this->event->tokenId)) {
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                $owner->id,
            );

            CollectionAccountApproval::updateOrCreate([
                'collection_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ], [
                'expiration' => $this->event->expiration,
            ]);

            return;
        }

        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $this->event->tokenId);
        // Fails if it doesn't find the token account
        $collectionAccount = $this->getTokenAccount(
            $collection->id,
            $token->id,
            $owner->id,
        );

        TokenAccountApproval::updateOrCreate([
            'token_account_id' => $collectionAccount->id,
            'wallet_id' => $operator->id,
        ], [
            'amount' => $this->event->amount,
            'expiration' => $this->event->expiration,
        ]);
    }

    public function log(): void
    {
        if (is_null($this->event->tokenId)) {
            Log::info(
                sprintf(
                    'Collection %s, Account %s approved %s.',
                    $this->event->collectionId,
                    $this->event->owner,
                    $this->event->operator,
                )
            );

            return;
        }

        Log::info(
            sprintf(
                'Collection %s, Token %s, Account %s approved %s units for %s',
                $this->event->collectionId,
                $this->event->tokenId,
                $this->event->owner,
                $this->event->amount,
                $this->event->operator,
            )
        );
    }

    public function broadcast(): void
    {
        if (is_null($this->event->tokenId)) {
            CollectionApproved::safeBroadcast(
                $this->event->collectionId,
                $this->event->operator,
                $this->event->expiration,
                $this->getTransaction($this->block, $this->event->extrinsicIndex),
            );

            return;
        }

        TokenApproved::safeBroadcast(
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->operator ?? 'unknown',
            $this->event->amount,
            $this->event->expiration,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
