<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenUnreserved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Unreserved as UnreservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Unreserved extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof UnreservedPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);
        $account = $this->firstOrStoreAccount($event->accountId);

        // Fails if it doesn't find the token account
        $tokenAccount = TokenAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])->firstOrFail();

        $tokenAccount->increment('balance', $event->amount);
        $tokenAccount->decrement('reserved_balance', $event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from($event->reserveId)->name,
        ]);

        if ($namedReserve !== null) {
            $amountLeft = $namedReserve->amount - $event->amount;
            $amountLeft > 0 ? $namedReserve->decrement('amount', $event->amount) : $namedReserve->delete();
        }

        Log::info(sprintf(
            'Changed named reserve of Collection %s (id: %s), Token #%s (id: %s) Account %s (id: %s) to amount %s of %s.',
            $event->collectionId,
            $collection->id,
            $event->tokenId,
            $token->id,
            $event->accountId,
            $account->id,
            $event->amount,
            $event->reserveId,
        ));

        TokenUnreserved::safeBroadcast(
            $collection,
            $token,
            $account,
            $event,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
