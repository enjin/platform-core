<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Endowed as EndowedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Endowed as EndowedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Endowed extends SubstrateEvent
{
    /** @var EndowedPolkadart */
    protected Event $event;

    public function run(): void
    {
    }

    public function log(): void
    {
        Log::info(sprintf(
            'Wallet %s was endowed with %s.',
            $this->event->account,
            $this->event->freeBalance,
        ));
    }

    public function broadcast(): void
    {
        EndowedEvent::safeBroadcast(
            $this->event->account,
            $this->event->freeBalance,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
        );
    }
}
