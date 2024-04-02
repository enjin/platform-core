<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMinted;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Minted as MintedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Minted implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof MintedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        ray($event);

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        ray($extrinsic);

        $recipient = WalletService::firstOrStore(['account' => Account::parseAccount($event->recipient)]);
        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);
        $token->update([
            'supply', ($token->supply + $event->amount) ?? 0,
        ]);

        $tokenAccount = TokenAccount::firstWhere([
            'wallet_id' => $recipient->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ]);
        $tokenAccount->increment('balance', $event->amount);

        Log::info(sprintf(
            'Minted %s units of Collection #%s (id: %s), Token #%s (id: %s) to %s (id: %s).',
            $event->amount,
            $event->collectionId,
            $collection->id,
            $event->tokenId,
            $token->id,
            $recipient->address,
            $recipient->id,
        ));

        TokenMinted::safeBroadcast(
            $token,
            WalletService::firstOrStore(['account' => Account::parseAccount($event->issuer)]),
            $recipient,
            $event->amount,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
