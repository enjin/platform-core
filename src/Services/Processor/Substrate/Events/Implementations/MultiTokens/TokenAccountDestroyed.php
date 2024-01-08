<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountDestroyed as TokenAccountDestroyedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenAccountDestroyed as TokenAccountDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class TokenAccountDestroyed implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenAccountDestroyedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);
        $account = WalletService::firstOrStore(['account' => $event->account]);
        $collectionAccount = $this->getCollectionAccount($collection->id, $account->id);
        $collectionAccount->decrement('account_count');
        $tokenAccount = TokenAccount::firstWhere([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ]);
        $tokenAccount->delete();

        Log::info(
            sprintf(
                'TokenAccount of Collection #%s (id %s), Token #%s (id %s) and account %s was deleted.',
                $event->collectionId,
                $collection->id,
                $event->tokenId,
                $token->id,
                $account->address,
            )
        );

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        TokenAccountDestroyedEvent::safeBroadcast(
            $collection,
            $token,
            $account,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
