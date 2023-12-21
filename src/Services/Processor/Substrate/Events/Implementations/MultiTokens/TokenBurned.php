<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenBurned as TokenBurnedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Burned as BurnedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class TokenBurned implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof BurnedPolkadart) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => $event->account]);
        $collection = $this->getCollection($event->collectionId);

        $token = Token::where(['collection_id' => $collection->id, 'token_chain_id' => $event->tokenId])->first();
        if ($token) {
            $token->decrement('supply', $event->amount);

            TokenAccount::firstWhere([
                'wallet_id' => $account->id,
                'collection_id' => $collection->id,
                'token_id' => $token->id,
            ])?->decrement('balance', $event->amount);

            Log::info(sprintf(
                'Burned %s units of Collection #%s (id: %s), Token #%s (id: %s) from %s (id: %s).',
                $event->amount,
                $event->tokenId,
                $token->id,
                $event->collectionId,
                $collection->id,
                $account->address,
                $account->id
            ));
        }

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        TokenBurnedEvent::safeBroadcast(
            $this->getCollection($event->collectionId),
            $event->tokenId,
            $event->account,
            $event->amount,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
