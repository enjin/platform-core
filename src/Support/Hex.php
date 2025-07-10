<?php

namespace Enjin\Platform\Support;

use Enjin\BlockchainTools\HexConverter;
use Exception;

class Hex
{
    public const string MIN_UINT = '0';
    public const string MAX_UINT8 = '255';
    public const string MAX_UINT16 = '65535';
    public const string MAX_UINT32 = '4294967295';
    public const string MAX_UINT64 = '18446744073709551615';
    public const string MAX_UINT128 = '340282366920938463463374607431768211455';
    public const string MAX_UINT256 = '115792089237316195423570985008687907853269984665640564039457584007913129639935';

    /**
     * Checks if a given string is encoded as hexadecimal.
     *
     * This function removes the '0x' prefix if it exists and then checks if the remaining string
     * consists only of valid hexadecimal characters (0-9, a-f, A-F).
     */
    public static function isHexEncoded(?string $input = null): bool
    {
        if (!$input) {
            return false;
        }

        // Remove the '0x' prefix if it exists
        if (str_starts_with($input, '0x')) {
            $input = substr($input, 2);
        }

        // Check if the remaining string is a valid hexadecimal
        return ctype_xdigit($input);
    }

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
        } catch (Exception) {
            return $value;
        }
    }
}
