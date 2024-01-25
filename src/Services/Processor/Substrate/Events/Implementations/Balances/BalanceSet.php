<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\BalanceSet as BalanceSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class BalanceSet implements SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof BalanceSetPolkadart) {
            return;
        }

        $whoAccount = WalletService::firstOrStore(['account' => $event->who]);

        Log::info(sprintf(
            'Balance of %s (id: %s) set to %s.',
            $whoAccount->address,
            $whoAccount->id,
            $event->free,
        ));

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        \Enjin\Platform\Events\Substrate\Balances\BalanceSet::safeBroadcast(
            $whoAccount,
            $event->free,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
