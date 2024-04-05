<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenTransferred;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Transferred as TransferredPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Transferred extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TransferredPolkadart) {
            return;
        }

        ray($event);

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);

        $fromAccount = WalletService::firstOrStore(['account' => $event->from]);
        TokenAccount::firstWhere([
            'wallet_id' => $fromAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->decrement('balance', $event->amount);

        $toAccount = WalletService::firstOrStore(['account' => $event->to]);
        TokenAccount::firstWhere([
            'wallet_id' => $toAccount->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])?->increment('balance', $event->amount);

        Log::info(sprintf(
            'Transferred %s units of token #%s (id: %s) in collection #%s (id: %s) to %s (id: %s).',
            $event->amount,
            $event->tokenId,
            $token->id,
            $event->collectionId,
            $collection->id,
            $toAccount->address,
            $toAccount->id,
        ));

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        TokenTransferred::safeBroadcast(
            $token,
            $fromAccount,
            $toAccount,
            $event->amount,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
