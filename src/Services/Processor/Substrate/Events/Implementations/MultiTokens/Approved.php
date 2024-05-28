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
        if (!$event instanceof ApprovedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($collectionId = $event->collectionId);
        $operator = $this->firstOrStoreAccount($event->operator);
        $owner =  $this->firstOrStoreAccount($event->owner);
        $transaction = $this->getTransaction($block, $event->extrinsicIndex);

        if ($event->tokenId === null) {
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                $owner->id,
            );

            CollectionAccountApproval::updateOrCreate(
                [
                    'collection_account_id' => $collectionAccount->id,
                    'wallet_id' => $operatorId = $operator->id,
                ],
                [
                    'expiration' => $event->expiration,
                ]
            );

            Log::info(
                sprintf(
                    'An approval for "%s" (id %s) was added to CollectionAccount %s, %s (id: %s).',
                    $event->operator,
                    $operatorId,
                    $event->owner,
                    $collectionId,
                    $collectionAccount->id,
                )
            );

            CollectionApproved::safeBroadcast(
                $collectionId,
                $operator->address,
                $event->expiration,
                $transaction
            );

            return;
        }

        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);
        // Fails if it doesn't find the token account
        $collectionAccount = $this->getTokenAccount(
            $collection->id,
            $token->id,
            $owner->id,
        );

        TokenAccountApproval::updateOrCreate(
            [
                'token_account_id' => $collectionAccount->id,
                'wallet_id' => $operatorId = $operator->id,
            ],
            [
                'amount' => $event->amount,
                'expiration' => $event->expiration,
            ]
        );

        Log::info(
            sprintf(
                'An approval for "%s" (id %s) was added to TokenAccount %s, %s, %s (id: %s).',
                $event->operator,
                $operatorId,
                $event->owner,
                $collectionId,
                $event->tokenId,
                $collectionAccount->id,
            )
        );

        TokenApproved::safeBroadcast(
            $collectionId,
            $event->tokenId,
            $operator->address ?? 'unknown',
            $event->amount,
            $event->expiration,
            $transaction
        );
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
