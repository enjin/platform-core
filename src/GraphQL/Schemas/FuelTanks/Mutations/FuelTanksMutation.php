<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Rebing\GraphQL\Support\Mutation as GraphQlMutation;

abstract class FuelTanksMutation extends GraphQlMutation implements PlatformGraphQlMutation
{
    use InFuelTanksSchema;

    /**
     * Adhoc rules.
     *
     * @var array
     */
    public static $adhocRules = [];

    /**
     * Get the blockchain method name from the graphql mutation name.
     */
    public function getMethodName(): string
    {
        return $this->attributes()['name'];
    }

    /**
     * Get the graphql mutation name.
     */
    public function getMutationName(): string
    {
        return $this->attributes()['name'];
    }

    /**
     * Get validation rules.
     */
    #[\Override]
    public function getRules(array $arguments = []): array
    {
        return collect(parent::getRules($arguments))->mergeRecursive(static::$adhocRules)->all();
    }

    public static function getEncodableParams(...$params): array
    {
        return [];
    }
}
