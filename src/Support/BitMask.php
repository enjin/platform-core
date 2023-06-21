<?php

namespace Enjin\Platform\Support;

class BitMask
{
    /**
     * Get bits from mask.
     */
    public static function getBits(int $mask): array
    {
        $bits = collect(array_fill(0, 32, false));

        return $bits->map(fn ($bit, $index) => self::getBit($index, $mask) ? $index : null)
            ->filter(fn ($bit) => isset($bit))
            ->values()
            ->toArray();
    }

    /**
     * Get bit from mask.
     */
    public static function getBit(int $bit, int $mask): bool
    {
        return ($mask & (1 << $bit)) != 0;
    }

    /**
     * Set bits in mask.
     */
    public static function setBits(array $bits, int $mask = 0): mixed
    {
        return collect($bits)->reduce(
            fn ($newMask, $bit) => self::setBit($bit, $newMask),
            $mask
        );
    }

    /**
     * Unset bits in mask.
     */
    public static function unsetBits(array $bits, int $mask = 0): mixed
    {
        return collect($bits)->reduce(
            fn ($newMask, $bit) => self::unsetBit($bit, $newMask),
            $mask
        );
    }

    /**
     * Toggle bits in mask.
     */
    public static function toggleBits(array $bits, int $mask = 0): mixed
    {
        return collect($bits)->reduce(
            fn ($newMask, $bit) => self::getBit($bit, $newMask) ? self::unsetBit($bit, $newMask) : self::setBit($bit, $newMask),
            $mask
        );
    }

    /**
     * Set bit in mask.
     */
    public static function setBit(int $bit, int $mask): int
    {
        return $mask | (1 << $bit);
    }

    /**
     * Unset bit in mask.
     */
    public static function unsetBit(int $bit, int $mask): int
    {
        return $mask & ~(1 << $bit);
    }
}
