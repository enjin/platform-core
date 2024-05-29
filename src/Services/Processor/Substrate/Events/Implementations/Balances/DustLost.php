<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\DustLost as DustLostPolkadart;
use Enjin\Platform\Events\Substrate\Balances\DustLost as DustLostEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class DustLost extends SubstrateEvent
{
    /** @var DustLostPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Wallet %s lost %s of dust.',
            $this->event->account,
            $this->event->amount,
        ));
    }

    public function broadcast(): void
    {
        DustLostEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
