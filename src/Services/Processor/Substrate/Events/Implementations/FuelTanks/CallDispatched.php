<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Events\Substrate\FuelTanks\CallDispatched as CallDispatchedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\CallDispatched as CallDispatchedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class CallDispatched extends SubstrateEvent
{
    /** @var CallDispatchedPolkadart */
    protected Event $event;

    /**
     * Handle the call dispatched event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        $this->firstOrStoreAccount($this->event->caller);
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'The caller %s has dispatched a call through FuelTank %s.',
                $this->event->caller,
                $this->event->tankId,
            )
        );
    }

    public function broadcast(): void
    {
        CallDispatchedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
