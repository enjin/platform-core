<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenUnreserved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Unreserved as UnreservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Unreserved extends SubstrateEvent
{
    /** @var UnreservedPolkadart */
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
        $account = $this->firstOrStoreAccount($this->event->accountId);

        // Fails if it doesn't find the token account
        $tokenAccount = TokenAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])->firstOrFail();

        $tokenAccount->increment('balance', $this->event->amount);
        $tokenAccount->decrement('reserved_balance', $this->event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from($this->event->reserveId)->name,
        ]);

        if ($namedReserve !== null) {
            $amountLeft = $namedReserve->amount - $this->event->amount;
            $amountLeft > 0 ? $namedReserve->decrement('amount', $this->event->amount) : $namedReserve->delete();
        }
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Changed named reserve of Collection %s (id: %s), Token #%s (id: %s) Account %s (id: %s) to amount %s of %s.',
            $this->event->collectionId,
            $collection->id,
            $this->event->tokenId,
            $token->id,
            $this->event->accountId,
            $account->id,
            $this->event->amount,
            $this->event->reserveId,
        ));
    }

    public function broadcast(): void
    {
        TokenUnreserved::safeBroadcast(
            $collection,
            $token,
            $account,
            $event,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
