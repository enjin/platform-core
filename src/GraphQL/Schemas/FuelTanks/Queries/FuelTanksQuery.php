<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Rebing\GraphQL\Support\Query as GraphQlQuery;

abstract class FuelTanksQuery extends GraphQlQuery implements PlatformGraphQlQuery
{
    use InFuelTanksSchema;

    /**
     * Adhoc rules.
     *
     * @var array
     */
    public static $adhocRules = [];

    /**
     * Get validation rules.
     */
    #[\Override]
    public function getRules(array $arguments = []): array
    {
        return collect(parent::getRules($arguments))->mergeRecursive(static::$adhocRules)->all();
    }
}
