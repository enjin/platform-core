<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountCreated as TokenAccountCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenAccountCreated as TokenAccountCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenAccountCreated extends SubstrateEvent
{
    /** @var TokenAccountCreatedPolkadart */
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
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $this->event->tokenId);
        $account = $this->firstOrStoreAccount($this->event->account);

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
    }

    public function log(): void
    {
        Log::info(
            sprintf(
                'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s was created.',
                $tokenAccount->id,
                $event->collectionId,
                $collection->id,
                $token->token_chain_id,
                $token->id,
                $account->address ?? 'unknown',
            )
        );
    }

    public function broadcast(): void
    {
        TokenAccountCreatedEvent::safeBroadcast(
            $collection,
            $token,
            $account,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
