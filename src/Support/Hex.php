<?php

namespace Enjin\Platform\Support;

use Enjin\BlockchainTools\HexConverter;

class Hex
{
    public const MIN_UINT = '0';
    public const MAX_UINT8 = '255';
    public const MAX_UINT16 = '65535';
    public const MAX_UINT32 = '4294967295';
    public const MAX_UINT64 = '18446744073709551615';
    public const MAX_UINT128 = '340282366920938463463374607431768211455';
    public const MAX_UINT256 = '115792089237316195423570985008687907853269984665640564039457584007913129639935';

    /**
     * Reverses the endian of a hex string.
     */
    public static function reverseEndian(string $hex): string
    {
        return implode('', array_reverse(str_split(HexConverter::unPrefix($hex), 2)));
    }

    /**
     * Converts a hex string to a string.
     */
    public static function safeConvertToString(string $value): string
    {
        try {
            $stringValue = HexConverter::hexToString($value);

            if (preg_match('/[^\x20-\x7e]/', $stringValue)) {
                return $value;
            }

            return $stringValue;
        } catch (\Exception $e) {
            return $value;
        }
    }
}
