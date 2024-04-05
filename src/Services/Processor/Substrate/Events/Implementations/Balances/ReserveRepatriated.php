<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\ReserveRepatriated as ReserveRepatriatedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\ReserveRepatriated as ReserveRepatriatedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class ReserveRepatriated extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ReserveRepatriatedPolkadart) {
            return;
        }

        $fromAccount = $this->firstOrStoreAccount($event->from);
        $toAccount = $this->firstOrStoreAccount($event->to);

        Log::info(sprintf(
            'Wallet %s (id: %s) has moved %s from reserve to %s at wallet %s (id: %s).',
            $fromAccount->address,
            $fromAccount->id,
            $event->amount,
            $event->destinationStatus,
            $toAccount->address,
            $toAccount->id,
        ));

        ReserveRepatriatedEvent::safeBroadcast(
            $fromAccount,
            $toAccount,
            $event->amount,
            $event->destinationStatus,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
