<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class FuelBudgetInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
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
