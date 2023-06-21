<?php

namespace Enjin\Platform\Support;

use Enjin\BlockchainTools\HexConverter;

class Blake2
{
    /**
     * Hashes a number to a 128 bit length.
     */
    public static function hashU128(string $number, ?int $bitLength = 128): string
    {
        $hexedNumber = HexConverter::uintToHex($number, 32);

        return self::hash(Hex::reverseEndian($hexedNumber), $bitLength);
    }

    /**
     * Hashes a number to a specified bit length.
     */
    public static function hash(string $data, ?int $bitLength = 256): string
    {
        $byteLength = (int) ceil($bitLength / 8);

        return bin2hex(sodium_crypto_generichash(
            hex2bin($data),
            '',
            $byteLength
        ));
    }

    /**
     * Hashes a number to a 128 bit length and encodes it.
     */
    public static function hashAndEncode(string $number): string
    {
        $hexedNumber = HexConverter::uintToHex($number, 32);
        $reversed = Hex::reverseEndian($hexedNumber);

        return self::hash($reversed, 128) . $reversed;
    }
}
