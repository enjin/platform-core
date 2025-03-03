<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits;

use Enjin\Platform\GraphQL\Schemas\Traits\GetsMiddleware;

trait InFuelTanksSchema
{
    use GetsMiddleware;

    /**
     * The schema name.
     */
    public static function getSchemaName(): string
    {
        return 'fuel-tanks';
    }

    /**
     * The schema network.
     */
    public static function getSchemaNetwork(): string
    {
        return '';
    }
}
