<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class FuelTankMutationInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
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
