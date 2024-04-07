<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenReserved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Reserved as ReservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Reserved extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ReservedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $event->tokenId);
        $account = $this->firstOrStoreAccount($event->accountId);

        throw new PlatformException('TokenReserved event is not implemented yet.');
        $tokenAccount = TokenAccount::firstOrFail([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ]);

        $tokenAccount->decrement('balance', $event->amount);
        $tokenAccount->increment('reserved_balance', $event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from($event->reserveId)->name,
        ]);

        if ($namedReserve == null) {
            TokenAccountNamedReserve::create([
                'token_account_id' => $tokenAccount->id,
                'pallet' => PalletIdentifier::from($event->reserveId)->name,
                'amount' => $event->amount,
            ]);
        } else {
            $namedReserve->increment('amount', $event->amount);
        }

        Log::info(sprintf(
            'Created named reserve of Collection %s (id: %s), Token #%s (id: %s) Account %s (id: %s) of amount %s to %s.',
            $event->collectionId,
            $collection->id,
            $event->tokenId,
            $token->id,
            $event->accountId,
            $account->id,
            $event->amount,
            $event->reserveId,
        ));

        // Missing getTransaction
        TokenReserved::safeBroadcast(
            $collection,
            $token,
            $account,
            $event
        );
    }
}
