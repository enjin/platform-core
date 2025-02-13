<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class AccountRuleInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'AccountRuleInputType',
            'description' => __('enjin-platform-fuel-tanks::input_type.account_rule.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    public function fields(): array
    {
        return [
            'whitelistedCallers' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform-fuel-tanks::input_type.account_rule.field.whitelistedCallers'),
            ],
            'requireToken' => [
                'type' => GraphQL::type('MultiTokenIdInput'),
                'description' => __('enjin-platform-fuel-tanks::input_type.account_rule.field.requireToken'),
            ],
        ];
    }
}
