<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\AccountAdded;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\AccountRemoved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\AccountRuleDataRemoved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\CallDispatched;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\FreezeStateMutated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\FuelTankCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\FuelTankDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\FuelTankMutated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\RuleSetInserted;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks\RuleSetRemoved;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum FuelTanksEventType: string
{
    use EnumExtensions;

    case FUEL_TANK_CREATED = 'FuelTankCreated';
    case FUEL_TANK_DESTROYED = 'FuelTankDestroyed';
    case FUEL_TANK_MUTATED = 'FuelTankMutated';
    case ACCOUNT_ADDED = 'AccountAdded';
    case ACCOUNT_REMOVED = 'AccountRemoved';
    case FREEZE_STATE_MUTATED = 'FreezeStateMutated';
    case ACCOUNT_RULE_DATA_REMOVED = 'AccountRuleDataRemoved';
    case RULE_SET_INSERTED = 'RuleSetInserted';
    case RULE_SET_REMOVED = 'RuleSetRemoved';
    case CALL_DISPATCHED = 'CallDispatched';

    /**
     * Get the processor for the event.
     */
    public function getProcessor($event, $block, $codec): SubstrateEvent
    {
        return match ($this) {
            self::FUEL_TANK_CREATED => new FuelTankCreated($event, $block, $codec),
            self::FUEL_TANK_DESTROYED => new FuelTankDestroyed($event, $block, $codec),
            self::FUEL_TANK_MUTATED => new FuelTankMutated($event, $block, $codec),
            self::ACCOUNT_ADDED => new AccountAdded($event, $block, $codec),
            self::ACCOUNT_REMOVED => new AccountRemoved($event, $block, $codec),
            self::FREEZE_STATE_MUTATED => new FreezeStateMutated($event, $block, $codec),
            self::ACCOUNT_RULE_DATA_REMOVED => new AccountRuleDataRemoved($event, $block, $codec),
            self::RULE_SET_INSERTED => new RuleSetInserted($event, $block, $codec),
            self::RULE_SET_REMOVED => new RuleSetRemoved($event, $block, $codec),
            self::CALL_DISPATCHED => new CallDispatched($event, $block, $codec),
        };
    }
}
