<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionUnapproved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenUnapproved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Unapproved as UnapprovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;

class Unapproved extends SubstrateEvent
{
    /** @var UnapprovedPolkadart */
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
        $owner = $this->firstOrStoreAccount($this->event->owner);

        if ($this->event->tokenId) {
            // Fails if it doesn't find the token
            $token = $this->getToken($collection->id, $this->event->tokenId);
            // Fails if it doesn't find the token account
            $collectionAccount = $this->getTokenAccount(
                $collection->id,
                $token->id,
                $owner->id
            );

            TokenAccountApproval::where([
                'token_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ])?->delete();


        } else {
            // Fails if it doesn't find the collection account
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                $owner->id
            );

            CollectionAccountApproval::where([
                'collection_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ])?->delete();
        }
    }

    public function log(): void
    {
        // TODO: Implement log() method.
    }

    public function broadcast(): void
    {
        TokenUnapproved::safeBroadcast(
            $this->event->collectionId,
            $this->event->tokenId,
            $operator->address,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );

        CollectionUnapproved::safeBroadcast(
            $this->event->collectionId,
            $operator->address,
            $this->getTransaction($this->block, $this->event->extrinsicIndex)
        );
    }
}
