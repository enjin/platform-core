<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenReserved;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\TokenAccountNamedReserve;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Reserved as ReservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Reserved implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ReservedPolkadart) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => Account::parseAccount($event->accountId)]);
        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);
        $tokenAccount = TokenAccount::firstWhere([
            'wallet_id' => $account->id,
            'collection_id' => $collection->id,
            'token_id' => $token->id,
        ]);
        $tokenAccount->decrement('balance', $event->amount);
        $tokenAccount->increment('reserved_balance', $event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => PalletIdentifier::from(HexConverter::hexToString($event->reserveId))->name,
        ]);

        if ($namedReserve == null) {
            TokenAccountNamedReserve::create([
                'token_account_id' => $tokenAccount->id,
                'pallet' => PalletIdentifier::from(HexConverter::hexToString($event->reserveId))->name,
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

        TokenReserved::safeBroadcast(
            $collection,
            $token,
            $account,
            $event
        );
    }
}
