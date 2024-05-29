<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Events\Substrate\Balances\BalanceSet as BalanceSetEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\BalanceSet as BalanceSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class BalanceSet extends SubstrateEvent
{
    /** @var BalanceSetPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Balance of %s set to %s.',
            $this->event->who,
            $this->event->free,
        ));
    }

    public function broadcast(): void
    {
        BalanceSetEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
