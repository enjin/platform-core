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
        if (!$event instanceof UnapprovedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $operator = $this->firstOrStoreAccount($event->operator);
        $transaction = $this->getTransaction($block, $event->extrinsicIndex);
        $owner = $this->firstOrStoreAccount($event->owner);

        if ($event->tokenId) {
            // Fails if it doesn't find the token
            $token = $this->getToken($collection->id, $event->tokenId);
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

            TokenUnapproved::safeBroadcast(
                $event->collectionId,
                $event->tokenId,
                $operator->address,
                $transaction
            );
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

            CollectionUnapproved::safeBroadcast(
                $event->collectionId,
                $operator->address,
                $transaction
            );
        }
    }

    public function log()
    {
        // TODO: Implement log() method.
    }

    public function broadcast()
    {
        // TODO: Implement broadcast() method.
    }
}
