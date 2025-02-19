<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\InputType;

class AccountRuleInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'AccountRuleInputType',
            'description' => __('enjin-platform::input_type.account_rule.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'whitelistedCallers' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::input_type.account_rule.field.whitelistedCallers'),
            ],
            'requireToken' => [
                'type' => GraphQL::type('MultiTokenIdInput'),
                'description' => __('enjin-platform::input_type.account_rule.field.requireToken'),
            ],
        ];
    }
}
