<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Reserved as ReservedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Reserved as ReservedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Reserved extends SubstrateEvent
{
    /** @var ReservedPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Reserved %s in wallet %s.',
            $this->event->amount,
            $this->event->who,
        ));
    }

    public function broadcast(): void
    {
        ReservedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
