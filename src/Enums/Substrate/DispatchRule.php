<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Models\Substrate\FuelTankRules;
use Enjin\Platform\Models\Substrate\MaxFuelBurnPerTransactionParams;
use Enjin\Platform\Models\Substrate\MinimumInfusionParams;
use Enjin\Platform\Models\Substrate\PermittedCallsParams;
use Enjin\Platform\Models\Substrate\PermittedExtrinsicsParams;
use Enjin\Platform\Models\Substrate\RequireSignatureParams;
use Enjin\Platform\Models\Substrate\RequireTokenParams;
use Enjin\Platform\Models\Substrate\TankFuelBudgetParams;
use Enjin\Platform\Models\Substrate\UserFuelBudgetParams;
use Enjin\Platform\Models\Substrate\WhitelistedCallersParams;
use Enjin\Platform\Models\Substrate\WhitelistedCollectionsParams;
use Enjin\Platform\Models\Substrate\WhitelistedPalletsParams;
use Enjin\Platform\Traits\EnumExtensions;

enum DispatchRule: string
{
    use EnumExtensions;

    case WHITELISTED_CALLERS = 'WhitelistedCallers';
    case WHITELISTED_COLLECTIONS = 'WhitelistedCollections';
    case MAX_FUEL_BURN_PER_TRANSACTION = 'MaxFuelBurnPerTransaction';
    case USER_FUEL_BUDGET = 'UserFuelBudget';
    case TANK_FUEL_BUDGET = 'TankFuelBudget';
    case REQUIRE_TOKEN = 'RequireToken';
    case PERMITTED_EXTRINSICS = 'PermittedExtrinsics';
    case PERMITTED_CALLS = 'PermittedCalls';
    case WHITELISTED_PALLETS = 'WhitelistedPallets';
    case REQUIRE_SIGNATURE = 'RequireSignature';
    case MINIMUM_INFUSION = 'MinimumInfusion';

    /**
     * Convert enum case to FuelTankRules.
     */
    public function toKind(): FuelTankRules
    {
        return match ($this) {
            self::WHITELISTED_CALLERS => new WhitelistedCallersParams(),
            self::WHITELISTED_COLLECTIONS => new WhitelistedCollectionsParams(),
            self::MAX_FUEL_BURN_PER_TRANSACTION => new MaxFuelBurnPerTransactionParams(''),
            self::USER_FUEL_BUDGET => new UserFuelBudgetParams('', ''),
            self::TANK_FUEL_BUDGET => new TankFuelBudgetParams('', ''),
            self::REQUIRE_TOKEN => new RequireTokenParams('', ''),
            self::PERMITTED_EXTRINSICS => new PermittedExtrinsicsParams(),
            self::PERMITTED_CALLS => new PermittedCallsParams(),
            self::WHITELISTED_PALLETS => new WhitelistedPalletsParams(),
            self::REQUIRE_SIGNATURE => new RequireSignatureParams(''),
            self::MINIMUM_INFUSION => new MinimumInfusionParams('')
        };
    }
}
