<?php

namespace Enjin\Platform\FuelTanks\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\FuelTanks\Events\Substrate\FuelTanks\FuelTankDestroyed as FuelTankDestroyedEvent;
use Enjin\Platform\FuelTanks\Models\Laravel\FuelTank;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\FuelTankDestroyed as FuelTankDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class FuelTankDestroyed extends SubstrateEvent
{
    /** @var FuelTankDestroyedPolkadart */
    protected Event $event;

    /**
     * Handle the fuel tank destroyed event.
     */
    public function run(): void
    {
        FuelTank::where(['public_key' => $this->event->tankId])?->delete();
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'FuelTank %s was destroyed.',
                $this->event->tankId,
            )
        );
    }

    public function broadcast(): void
    {
        FuelTankDestroyedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
