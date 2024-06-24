<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Withdraw as WithdrawPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\Balances\Withdraw as WithdrawEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Withdraw extends SubstrateEvent
{
    /** @var WithdrawPolkadart */
    protected Event $event;

    public function run(): void {}

    public function log(): void
    {
        Log::debug(sprintf(
            'Withdraw %s from wallet %s.',
            $this->event->amount,
            $this->event->who,
        ));
    }

    public function broadcast(): void
    {
        WithdrawEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
