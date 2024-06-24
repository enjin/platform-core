<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Unreserved as UnreservedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Unreserved as UnreservedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Unreserved extends SubstrateEvent
{
    /** @var UnreservedPolkadart */
    protected Event $event;

    public function run(): void {}

    public function log(): void
    {
        Log::debug(sprintf(
            'Reserved %s in wallet %s.',
            $this->event->amount,
            $this->event->who,
        ));
    }

    public function broadcast(): void
    {
        UnreservedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
