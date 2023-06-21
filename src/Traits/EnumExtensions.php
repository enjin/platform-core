<?php

namespace Enjin\Platform\Traits;

use Illuminate\Support\Collection;

trait EnumExtensions
{
    /**
     * Get enum cases as collection.
     */
    public static function casesAsCollection(): Collection
    {
        return collect(self::cases());
    }

    /**
     * Get enum cases as array.
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
     * Get enum cases as collection.
     */
    public static function caseNamesAsCollection()
    {
        return self::casesAsCollection()->pluck('name');
    }

    /**
     * Get enum cases value as collection.
     */
    public static function caseValuesAsCollection()
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
