<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenAccountDestroyed as TokenAccountDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenAccountDestroyed as TokenAccountDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenAccountDestroyed extends SubstrateEvent
{
    /** @var TokenAccountDestroyedPolkadart */
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
        $collectionAccount->decrement('account_count');

        TokenAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])->delete();
    }

    public function log(): void
    {
        Log::info(
            sprintf(
                'TokenAccount of Collection #%s (id %s), Token #%s (id %s) and account %s was deleted.',
                $event->collectionId,
                $collection->id,
                $event->tokenId,
                $token->id,
                $account->address ?? 'unknown',
            )
        );
    }

    public function broadcast(): void
    {
        TokenAccountDestroyedEvent::safeBroadcast(
            $collection,
            $token,
            $account,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
