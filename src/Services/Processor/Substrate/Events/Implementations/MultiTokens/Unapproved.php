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
use Illuminate\Support\Facades\Log;

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

        if (is_null($this->event->tokenId)) {
            // Fails if it doesn't find the collection account
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                $owner->id
            );

            CollectionAccountApproval::where([
                'collection_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ])?->delete();

            return;
        }

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
    }

    public function log(): void
    {
        if (is_null($this->event->tokenId)) {
            Log::debug(
                sprintf(
                    'Collection %s, Account %s unapproved %s.',
                    $this->event->collectionId,
                    $this->event->owner,
                    $this->event->operator,
                )
            );

            return;
        }

        Log::debug(
            sprintf(
                'Collection %s, Token %s, Account %s unapproved %s',
                $this->event->collectionId,
                $this->event->tokenId,
                $this->event->owner,
                $this->event->operator,
            )
        );
    }

    public function broadcast(): void
    {
        if (is_null($this->event->tokenId)) {
            CollectionUnapproved::safeBroadcast(
                $this->event,
                $this->getTransaction($this->block, $this->event->extrinsicIndex),
                $this->extra,
            );

            return;
        }

        TokenUnapproved::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
