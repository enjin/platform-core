<?php

namespace Enjin\Platform\FuelTanks\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\FuelTanks\Events\Substrate\FuelTanks\FreezeStateMutated as FreezeStateMutatedEvent;
use Enjin\Platform\FuelTanks\Models\DispatchRule;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\FreezeStateMutated as FreezeStateMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class FreezeStateMutated extends SubstrateEvent
{
    /** @var FreezeStateMutatedPolkadart */
    protected Event $event;

    /**
     * Handle the freeze state mutated event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        // Fail if it doesn't find the fuel tank
        $fuelTank = $this->getFuelTank($this->event->tankId);

        if (is_null($this->event->ruleSetId)) {
            $fuelTank->is_frozen = $this->event->isFrozen;
            $fuelTank->save();

            return;
        }

        DispatchRule::where([
            'fuel_tank_id' => $fuelTank->id,
            'rule_set_id' => $this->event->ruleSetId,
        ])?->update(['is_frozen' => $this->event->isFrozen]);
    }

    public function log(): void
    {
        if (is_null($this->event->ruleSetId)) {
            Log::debug(
                sprintf(
                    'FuelTank %s was %s.',
                    $this->event->tankId,
                    $this->event->isFrozen ? 'frozen' : 'thawed',
                )
            );

            return;
        }

        Log::debug(
            sprintf(
                'The rule set id %s of fuel tank %s was %s.',
                $this->event->ruleSetId,
                $this->event->tankId,
                $this->event->isFrozen ? 'frozen' : 'thawed',
            )
        );
    }

    public function broadcast(): void
    {
        FreezeStateMutatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
