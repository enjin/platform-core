<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenBurned as TokenBurnedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Burned as BurnedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenBurned extends SubstrateEvent
{
    /** @var BurnedPolkadart */
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
        $token = Token::firstWhere(['collection_id' => $collection->id, 'token_chain_id' => $this->event->tokenId]);
        $account = $this->firstOrStoreAccount($this->event->account);

        if ($token) {
            $token->decrement('supply', $this->event->amount);

            TokenAccount::firstWhere([
                'wallet_id' => $account->id,
                'collection_id' => $collection->id,
                'token_id' => $token->id,
            ])?->decrement('balance', $this->event->amount);


        }


    }

    public function log(): void
    {
        Log::info(sprintf(
            'Burned %s units of Collection #%s (id: %s), Token #%s (id: %s) from %s (id: %s).',
            $event->amount,
            $event->tokenId,
            $token->id,
            $event->collectionId,
            $collection->id,
            $account->address ?? 'unknown',
            $account->id ?? 'unknown',
        ));
    }

    public function broadcast(): void
    {
        TokenBurnedEvent::safeBroadcast(
            $collection,
            $event->tokenId,
            $event->account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }
}
