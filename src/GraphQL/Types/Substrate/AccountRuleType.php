<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Arr;
use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class AccountRuleType extends Type implements PlatformGraphQlType
{
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
                'resolve' => fn ($r) => Arr::get($r->rule, 'isTypeOf'),
            ],
            'value' => [
                'type' => GraphQL::type('Object!'),
                'description' => __('enjin-platform::type.fuel_tank_rule.field.value'),
                'resolve' => fn ($r) => Arr::except($r->rule, 'isTypeOf'),
            ],
        ];
    }
}
