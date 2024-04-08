<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenBurned as TokenBurnedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Burned as BurnedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenBurned extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof BurnedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $token = Token::firstWhere(['collection_id' => $collection->id, 'token_chain_id' => $event->tokenId]);
        $account = $this->firstOrStoreAccount($event->account);

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
                $account->address ?? 'unknown',
                $account->id ?? 'unknown',
            ));
        }

        TokenBurnedEvent::safeBroadcast(
            $collection,
            $event->tokenId,
            $event->account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex)
        );
    }
}
