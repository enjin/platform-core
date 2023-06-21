<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Traits\EnumExtensions;

enum PalletIdentifier: string
{
    use EnumExtensions;

    case MARKETPLACE = 'marktplc';
    case MULTI_TOKENS = 'multoken';
    case FUEL_TANK = 'fueltank';

    /**
     * Get the pallet identifier from a hex string.
     */
    public static function fromHex(string $hex): self
    {
        return self::from(HexConverter::hexToString($hex));
    }
}
