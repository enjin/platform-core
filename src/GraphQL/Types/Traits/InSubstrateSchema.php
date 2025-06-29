<?php

namespace Enjin\Platform\GraphQL\Types\Traits;

trait InSubstrateSchema
{
    /**
     * Get schema name.
     */
    public static function getSchemaName(): string
    {
        return '';
    }

    /**
     * Get schema network.
     */
    public static function getSchemaNetwork(): string
    {
        return 'substrate';
    }
}
