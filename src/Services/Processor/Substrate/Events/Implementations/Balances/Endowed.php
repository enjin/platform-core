<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Endowed as EndowedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Endowed implements SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof EndowedPolkadart) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => $event->account]);

        Log::info(sprintf(
            'Wallet %s (id: %s) was endowed with %s.',
            $account->address,
            $account->id,
            $event->freeBalance,
        ));

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        \Enjin\Platform\Events\Substrate\Balances\Endowed::safeBroadcast(
            $account,
            $event->freeBalance,
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
