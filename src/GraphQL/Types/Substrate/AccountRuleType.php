<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\FuelTanks\GraphQL\Traits\InFuelTanksSchema;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class AccountRuleType extends Type
{
    use HasSelectFields;
    use InFuelTanksSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'AccountRule',
            'description' => __('enjin-platform::type.fuel_tank_rule.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'rule' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.fuel_tank_rule.field.rule'),
            ],
            'value' => [
                'type' => GraphQL::type('Object!'),
                'description' => __('enjin-platform::type.fuel_tank_rule.field.value'),
            ],
        ];
    }
}
