<?php

namespace Enjin\Platform\Interfaces;

interface PlatformGraphQlType
{
    /**
     * Get the schema name.
     */
    public static function getSchemaName(): string;

    /**
     * Get the schema network.
     */
    public static function getSchemaNetwork(): string;
}
