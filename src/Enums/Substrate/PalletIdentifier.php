<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Traits\EnumExtensions;

enum PalletIdentifier: string
{
    use EnumExtensions;

    case MARKETPLACE = 'Marketplace';
    case MULTI_TOKENS = 'MultiTokens';
    case FUEL_TANK = 'FuelTank';

    /**
     * Get the pallet identifier from a hex string.
     */
    public static function fromHex(string $hex): self
    {
        return self::from(HexConverter::hexToString($hex));
    }
}
