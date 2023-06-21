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
     * Get schmea network.
     */
    public static function getSchemaNetwork(): string
    {
        return 'substrate';
    }
}
