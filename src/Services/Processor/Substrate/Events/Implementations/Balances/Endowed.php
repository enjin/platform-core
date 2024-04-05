<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Endowed as EndowedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Endowed as EndowedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Endowed extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof EndowedPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->account);

        Log::info(sprintf(
            'Wallet %s (id: %s) was endowed with %s.',
            $account->address,
            $account->id,
            $event->freeBalance,
        ));

        EndowedEvent::safeBroadcast(
            $account,
            $event->freeBalance,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
