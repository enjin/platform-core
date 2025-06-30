<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class FuelBudgetInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'FuelBudgetInputType',
            'description' => __('enjin-platform::input_type.fuel_budget.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::input_type.fuel_budget.field.amount'),
            ],
            'resetPeriod' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::input_type.fuel_budget.field.resetPeriod'),
            ],
        ];
    }
}
