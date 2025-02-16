<?php

namespace Enjin\Platform\Support;

use Enjin\BlockchainTools\HexConverter;

class Twox
{
    /**
     * Hashes a number to a 128 bit length.
     */
    public static function hash(string $data, ?int $bitLength = 128): string
    {
        $data = HexConverter::hasPrefix($data) ? pack('H*', HexConverter::unPrefix($data)) : $data;
        $rounds = (int) ceil($bitLength / 64);

        $hashes = [];
        for ($seed = 0; $seed < $rounds; $seed++) {
            $hashes[] = Hex::reverseEndian(hash('xxh64', $data, false, ['seed' => $seed]));
        }

        return implode('', $hashes);
    }
}
