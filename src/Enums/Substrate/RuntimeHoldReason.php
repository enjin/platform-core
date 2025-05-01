<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum RuntimeHoldReason: int
{
    use EnumExtensions;

    case PREIMAGE = 7;
    case SAFE_MODE = 64;
    case COUNCIL = 13;
    case TECHNICAL_COMMITTEE = 14;
    case COLLATOR_STAKING = 21;
    case MULTI_TOKENS = 40;
    case FUEL_TANKS = 54;
    case MARKETPLACE = 50;

    /**
     * Get the pallet identifier from a hex string.
     */
    public static function fromIndex(int $index): self
    {
        return self::from($index);
    }
}
