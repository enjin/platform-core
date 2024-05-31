<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\ReserveRepatriated as ReserveRepatriatedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\ReserveRepatriated as ReserveRepatriatedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class ReserveRepatriated extends SubstrateEvent
{
    /** @var ReserveRepatriatedPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Wallet %s has moved %s from reserve to %s at wallet %s.',
            $this->event->from,
            $this->event->amount,
            $this->event->destinationStatus,
            $this->event->to,
        ));
    }

    public function broadcast(): void
    {
        ReserveRepatriatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
