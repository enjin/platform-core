<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Transfer as TransferPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\Balances\Transfer as TransferEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Transfer extends SubstrateEvent
{
    /** @var TransferPolkadart */
    protected Event $event;

    public function run(): void {}

    public function log(): void
    {
        Log::debug(sprintf(
            'Wallet %s has transferred %s to wallet %s.',
            $this->event->from,
            $this->event->amount,
            $this->event->to,
        ));
    }

    public function broadcast(): void
    {
        TransferEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
