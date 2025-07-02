<?php

namespace Enjin\Platform\Traits;

use Illuminate\Support\Collection;

trait EnumExtensions
{
    /**
     * Get enum cases as a collection.
     */
    public static function casesAsCollection(): Collection
    {
        return collect(self::cases());
    }

    /**
     * Get enum cases as an array.
     */
    public static function caseNamesAsArray(): array
    {
        return self::caseNamesAsCollection()->all();
    }

    /**
     * Get enum values as array.
     */
    public static function caseValuesAsArray(): array
    {
        return self::caseValuesAsCollection()->all();
    }

    /**
     * Get enum cases as a collection.
     */
    public static function caseNamesAsCollection(): Collection
    {
        return self::casesAsCollection()->pluck('name');
    }

    /**
     * Get enum cases value as a collection.
     */
    public static function caseValuesAsCollection(): Collection
    {
        return self::casesAsCollection()->pluck('value');
    }

    /**
     * Get enum case by value.
     */
    public static function getEnumCase(string $caseName)
    {
        return self::casesAsCollection()->filter(fn ($case) => $case->name == $caseName)->first();
    }
}
