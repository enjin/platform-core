<?php

namespace Enjin\Platform\Interfaces;

interface PlatformGraphQlQuery
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
