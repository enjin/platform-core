<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenReserved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Reserved as ReservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Reserved extends SubstrateEvent
{
    /** @var ReservedPolkadart */
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
        $tokenAccount->decrement('balance', $this->event->amount);
        $tokenAccount->increment('reserved_balance', $this->event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from($this->event->reserveId)->name,
        ]);

        if (is_null($namedReserve)) {
            TokenAccountNamedReserve::create([
                'token_account_id' => $tokenAccount->id,
                'pallet' => PalletIdentifier::from($this->event->reserveId)->name,
                'amount' => $this->event->amount,
            ]);
        } else {
            $namedReserve->increment('amount', $this->event->amount);
        }
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Created named reserve %s of amount %s for collection %s, token %s, account %s.',
            $this->event->reserveId,
            $this->event->amount,
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->accountId,
        ));
    }

    public function broadcast(): void
    {
        TokenReserved::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
