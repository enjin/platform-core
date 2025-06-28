<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class DispatchRuleType extends Type implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchRule',
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
            'ruleSetId' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.ruleSetId'),
                'alias' => 'rule_set_id',
            ],
            'value' => [
                'type' => GraphQL::type('Object!'),
                'description' => __('enjin-platform::type.fuel_tank_rule.field.value'),
            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank_rule.field.value'),
                'alias' => 'is_frozen',
            ],
        ];
    }
}
