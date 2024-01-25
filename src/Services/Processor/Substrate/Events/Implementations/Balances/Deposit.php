<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Deposit as DepositPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Deposit implements SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof DepositPolkadart) {
            return;
        }

        $whoAccount = WalletService::firstOrStore(['account' => $event->who]);

        Log::info(sprintf(
            'Wallet %s (id: %s) made a deposit of %s.',
            $whoAccount->address,
            $whoAccount->id,
            $event->amount,
        ));

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        \Enjin\Platform\Events\Substrate\Balances\Deposit::safeBroadcast(
            $whoAccount,
            $event->amount,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
