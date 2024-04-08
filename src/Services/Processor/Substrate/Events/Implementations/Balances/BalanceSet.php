<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\BalanceSet as BalanceSetPolkadart;
use Enjin\Platform\Events\Substrate\Balances\BalanceSet as BalanceSetEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class BalanceSet extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof BalanceSetPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->who);

        Log::info(sprintf(
            'Balance of %s (id: %s) set to %s.',
            $account->address,
            $account->id,
            $event->free,
        ));

        BalanceSetEvent::safeBroadcast(
            $account,
            $event->free,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
