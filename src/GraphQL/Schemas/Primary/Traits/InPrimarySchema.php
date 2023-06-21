<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

use Enjin\Platform\GraphQL\Schemas\Traits\GetsMiddleware;

trait InPrimarySchema
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
        return '';
    }
}
