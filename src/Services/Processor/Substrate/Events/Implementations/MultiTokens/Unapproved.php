<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionUnapproved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenUnapproved;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Unapproved as UnapprovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;

class Unapproved extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof UnapprovedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection(
            $collectionId = $event->collectionId
        );
        $operator = WalletService::firstOrStore(['account' => Account::parseAccount($event->operator)]);

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        if ($tokenId = $event->tokenId) {
            $token = $this->getToken($collection->id, $tokenId);
            $collectionAccount = $this->getTokenAccount(
                $collection->id,
                $token->id,
                WalletService::firstOrStore(['account' => Account::parseAccount($event->owner)])->id
            );

            TokenAccountApproval::where([
                'token_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ])?->delete();

            TokenUnapproved::safeBroadcast(
                $collectionId,
                $tokenId,
                $operator->address,
                $transaction
            );
        } else {
            $collectionAccount = $this->getCollectionAccount(
                $collection->id,
                WalletService::firstOrStore(['account' => Account::parseAccount($event->owner)])->id
            );

            CollectionAccountApproval::where([
                'collection_account_id' => $collectionAccount->id,
                'wallet_id' => $operator->id,
            ])?->delete();

            CollectionUnapproved::safeBroadcast(
                $collectionId,
                $operator->address,
                $transaction
            );
        }
    }
}
