<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\TokenAccountNamedReserve;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Reserved as UnreservedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Unreserved implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof UnreservedPolkadart) {
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
        $tokenAccount->decrement('reserved_balance', $event->amount);

        $namedReserve = TokenAccountNamedReserve::firstWhere([
            'token_account_id' => $tokenAccount->id,
            'pallet' => HexConverter::hexToString($event->reserveId),
        ]);

        if ($namedReserve->amount - $event->amount == 0) {
            $namedReserve->delete();
        } else {
            $namedReserve->decrement('amount', $event->amount);
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
        );
    }
}
