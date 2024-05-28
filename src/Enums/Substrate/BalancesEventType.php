<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\BalanceSet;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Deposit;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\DustLost;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Endowed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Reserved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\ReserveRepatriated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Slashed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Transfer;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Unreserved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances\Withdraw;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum BalancesEventType: string
{
    use EnumExtensions;

    case DUST_LOST = 'DustLost';
    case ENDOWED = 'Endowed';
    case RESERVE_REPATRIATED = 'ReserveRepatriated';
    case RESERVED = 'Reserved';
    case SLASHED = 'Slashed';
    case TRANSFER = 'Transfer';
    case UNRESERVED = 'Unreserved';
    case WITHDRAW = 'Withdraw';
    case BALANCE_SET = 'BalanceSet';
    case DEPOSIT = 'Deposit';

    /**
     * Get the processor for the event.
     */
    public function getProcessor($event, $block, $codec): SubstrateEvent
    {
        return match ($this) {
            self::DUST_LOST => new DustLost($event, $block, $codec),
            self::ENDOWED => new Endowed($event, $block, $codec),
            self::RESERVE_REPATRIATED => new ReserveRepatriated($event, $block, $codec),
            self::RESERVED => new Reserved($event, $block, $codec),
            self::SLASHED => new Slashed($event, $block, $codec),
            self::TRANSFER => new Transfer($event, $block, $codec),
            self::UNRESERVED => new Unreserved($event, $block, $codec),
            self::WITHDRAW => new Withdraw($event, $block, $codec),
            self::BALANCE_SET => new BalanceSet($event, $block, $codec),
            self::DEPOSIT => new Deposit($event, $block, $codec),
        };
    }
}
