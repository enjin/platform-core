<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\ReserveRepatriated as ReserveRepatriatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class ReserveRepatriated implements SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ReserveRepatriatedPolkadart) {
            return;
        }

        $fromAccount = WalletService::firstOrStore(['account' => $event->from]);
        $toAccount = WalletService::firstOrStore(['account' => $event->to]);

        Log::info(sprintf(
            'Wallet %s (id: %s) has moved %s from reserve to %s at wallet %s (id: %s).',
            $fromAccount->address,
            $fromAccount->id,
            $event->amount,
            $event->destinationStatus,
            $toAccount->address,
            $toAccount->id,
        ));

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        \Enjin\Platform\Events\Substrate\Balances\ReserveRepatriated::safeBroadcast(
            $fromAccount,
            $toAccount,
            $event->amount,
            $event->destinationStatus,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
