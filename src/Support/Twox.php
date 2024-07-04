<?php

namespace Enjin\Platform\Support;

use Crypto\sr25519;

class Twox
{
    public sr25519 $sr;

    public function __construct()
    {
        $this->sr = new sr25519();
    }

    public function ByHasherName(string $hasher, string $hex): string
    {
        return match ($hasher) {
            'Twox128' => sprintf('%s', $this->TwoxHash($hex, 128)),
            'Twox256' => sprintf('%s', $this->TwoxHash($hex, 256)),
            'Twox64Concat' => sprintf('%s%s', $this->XXHash64(0, $hex), self::trimHex($hex)),
            default => throw new \InvalidArgumentException(sprintf('invalid hasher %s', $hasher)),
        };
    }

    /**
     * XXHash64 hash
     * https://github.com/Cyan4973/xxHash
     * The algorithm takes an input a message of arbitrary length and a seed value.
     */
    public function XXHash64(int $seed, string $data): string
    {
        return $this->sr->XXHash64CheckSum($seed, $data);
    }

    /**
     * Twox hasher with $bitLength
     * https://docs.rs/frame-support/2.0.0-rc4/frame_support/struct.Twox128.html
     * https://docs.rs/frame-support/2.0.0-rc4/frame_support/struct.Twox256.html
     *  Twox128 with 128 $bitLength
     *  Twox256 with 256 $bitLength.
     */
    public function TwoxHash(string $data, int $bitLength): string
    {
        $hash = '';
        for ($seed = 0; $seed < ceil($bitLength / 64); $seed++) {
            $hash .= $this->sr->XXHash64CheckSum($seed, $data);
        }

        return $hash;
    }

    /**
     * Trim 0x prefix.
     *
     * @param  $hexString  string
     */
    public static function trimHex(string $hexString): string
    {
        return preg_replace('/0x/', '', $hexString);
    }

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
