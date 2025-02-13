<?php

namespace Enjin\Platform\FuelTanks\Enums\Substrate;

use Enjin\Platform\Models\Substrate\FuelTankRules;
use Enjin\Platform\Models\Substrate\RequireTokenParams;
use Enjin\Platform\Models\Substrate\WhitelistedCallersParams;
use Enjin\Platform\Traits\EnumExtensions;

enum AccountRule: string
{
    use EnumExtensions;

    case WHITELISTED_CALLERS = 'WhitelistedCallers';
    case REQUIRE_TOKEN = 'RequireToken';

    /**
     * Convert enum case to FuelTankRules.
     */
    public function toKind(): FuelTankRules
    {
        return match ($this) {
            self::WHITELISTED_CALLERS => new WhitelistedCallersParams(),
            self::REQUIRE_TOKEN => new RequireTokenParams('', '')
        };
    }
}
