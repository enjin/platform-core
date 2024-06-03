<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenUnreserved;
use Enjin\Platform\Exceptions\PlatformException;
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
        $tokenAccount = $this->getTokenAccount($collection->id, $token->id, $account->id);

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
        Log::debug(sprintf(
            'Changed named reserve %s to %s for collection %s, token %s and account %s.',
            $this->event->reserveId,
            $this->event->amount,
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->accountId,
        ));
    }

    public function broadcast(): void
    {
        TokenUnreserved::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
