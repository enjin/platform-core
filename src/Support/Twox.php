<?php

namespace Enjin\Platform\Support;

class Twox
{
    /**
     * Hashes a number to a 128 bit length.
     */
    public static function hash(string $data, ?int $bitLength = 128): string
    {
        $rounds = (int) ceil($bitLength / 64);

        $hashes = [];
        for ($seed = 0; $seed < $rounds; $seed++) {
            $hashes[] = Hex::reverseEndian(hash('xxh64', $data, false, ['seed' => $seed]));
        }

        return implode('', $hashes);
    }
}
