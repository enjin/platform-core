<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Events\Substrate\Balances\Deposit as DepositEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Deposit as DepositPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Deposit extends SubstrateEvent
{
    /** @var DepositPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Wallet %s made a deposit of %s.',
            $this->event->who,
            $this->event->amount,
        ));
    }

    public function broadcast(): void
    {
        DepositEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
