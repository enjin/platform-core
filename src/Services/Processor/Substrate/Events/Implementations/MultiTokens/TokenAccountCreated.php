<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountCreated as TokenAccountCreatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenAccountCreated as TokenAccountCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class TokenAccountCreated implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenAccountCreatedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => $event->account]);
        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);
        $collectionAccount = $this->getCollectionAccount($collection->id, $account->id);
        $collectionAccount->increment('account_count');
        $tokenAccount = TokenAccount::create([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
            'balance' => 0, // The balances are updated on Mint event
            'reserved_balance' => 0,
            'is_frozen' => false,
        ]);

        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s was created.',
                $tokenAccount->id,
                $event->collectionId,
                $collection->id,
                $token->token_chain_id,
                $token->id,
                $account->address,
            )
        );

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        TokenAccountCreatedEvent::safeBroadcast(
            $collection,
            $token,
            $account,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
