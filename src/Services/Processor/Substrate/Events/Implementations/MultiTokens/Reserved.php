<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenReserved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\TokenAccount;
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

        $tokenAccount = TokenAccount::where([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ])->firstOrFail();

        $tokenAccount->decrement('balance', $this->event->amount);
        $tokenAccount->increment('reserved_balance', $this->event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from($this->event->reserveId)->name,
        ]);

        if ($namedReserve === null) {
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
    }

    public function broadcast(): void
    {
        TokenReserved::safeBroadcast(
            $collection,
            $token,
            $account,
            $event,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
