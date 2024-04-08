<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMinted;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Minted as MintedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Minted extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof MintedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);

        $transaction = $this->getTransaction($block, $event->extrinsicIndex);
        $recipient = $this->firstOrStoreAccount($event->recipient);

        $token->update([
            'supply', gmp_strval(gmp_add($token->supply, $event->amount)) ?? 0,
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
            $recipient->address ?? 'unknown',
            $recipient->id ?? 'unknown',
        ));

        TokenMinted::safeBroadcast(
            $token,
            $this->firstOrStoreAccount($event->issuer),
            $recipient,
            $event->amount,
            $transaction,
        );
    }
}
