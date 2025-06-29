<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits;

use Enjin\Platform\GraphQL\Schemas\Traits\GetsMiddleware;

trait InPrimarySubstrateSchema
{
    use GetsMiddleware;

    /**
     * Get schema name.
     */
    public static function getSchemaName(): string
    {
        return 'primary';
    }

    /**
     * Get schema network.
     */
    public static function getSchemaNetwork(): string
    {
        return 'substrate';
    }
}
