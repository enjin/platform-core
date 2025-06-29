<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class FuelTankMutationInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'FuelTankMutationInputType',
            'description' => __('enjin-platform::input_type.fuel_tank_mutation.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'reservesAccountCreationDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesAccountCreationDeposit'),
            ],
            'coveragePolicy' => [
                'type' => GraphQL::type('CoveragePolicy'),
                'description' => __('enjin-platform::type.fuel_tank.field.coveragePolicy'),
            ],
            'accountRules' => [
                'type' => GraphQL::type('AccountRuleInputType'),
                'description' => __('enjin-platform::input_type.account_rule.description'),
            ],
            // Deprecated
            'reservesExistentialDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesExistentialDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.reservesExistentialDeposit'),
            ],
            'providesDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.providesDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.providesDeposit'),
            ],
        ];
    }
}
