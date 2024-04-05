<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionApproved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenApproved;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Approved as ApprovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Approved extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ApprovedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection(
            $collectionId = $event->collectionId,
        );
        $operator = $this->firstOrStoreAccount($event->operator);



        
        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        if ($tokenId = $event->tokenId) {
            $token = $this->getToken($collection->id, $tokenId);
            $collectionAccount = $this->getTokenAccount(
                $collection->id,
                $token->id,
                WalletService::firstOrStore(['account' => Account::parseAccount($event->owner)])->id,
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
                    $tokenId,
                    $collectionAccount->id,
                )
            );

            TokenApproved::safeBroadcast(
                $collectionId,
                $tokenId,
                $operator->address,
                $event->amount,
                $event->expiration,
                $transaction
            );
        } else {
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                WalletService::firstOrStore(['account' => Account::parseAccount($event->owner)])->id,
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
        }
    }
}
