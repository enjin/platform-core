<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Events\Substrate\FuelTanks\RuleSetRemoved as RuleSetRemovedEvent;
use Enjin\Platform\Models\DispatchRule;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\RuleSetRemoved as RuleSetRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class RuleSetRemoved extends SubstrateEvent
{
    /** @var RuleSetRemovedPolkadart */
    protected Event $event;

    /**
     * Handle the rule set removed event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        // Fail if it doesn't find the fuel tank
        $fuelTank = $this->getFuelTank($this->event->tankId);

        DispatchRule::where([
            'fuel_tank_id' => $fuelTank->id,
            'rule_set_id' => $this->event->ruleSetId,
        ])?->delete();
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'The rule set %s from FuelTank %s was removed.',
                $this->event->ruleSetId,
                $this->event->tankId,
            )
        );
    }

    public function broadcast(): void
    {
        RuleSetRemovedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
