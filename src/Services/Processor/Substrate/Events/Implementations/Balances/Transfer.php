<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Transfer as TransferPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Transfer implements SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TransferPolkadart || is_null($event->extrinsicIndex)) {
            return;
        }

        ray($event);

        $fromAccount = WalletService::firstOrStore(['account' => $event->from]);
        $toAccount = WalletService::firstOrStore(['account' => $event->to]);

        Log::info(sprintf(
            'Wallet %s (id: %s) has transferred %s to wallet %s (id: %s).',
            $fromAccount->address,
            $fromAccount->id,
            $event->amount,
            $toAccount->address,
            $toAccount->id,
        ));

        ray($block->extrinsics);

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        ray($extrinsic);

        \Enjin\Platform\Events\Substrate\Balances\Transfer::safeBroadcast(
            $fromAccount,
            $toAccount,
            $event->amount,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
