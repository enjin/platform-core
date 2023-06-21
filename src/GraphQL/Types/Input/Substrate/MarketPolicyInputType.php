<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class MarketPolicyInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'MarketPolicy',
            'description' => __('enjin-platform::input_type.market_policy.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'royalty' => [
                'type' => GraphQL::type('RoyaltyInput!'),
                'description' => __('enjin-platform::input_type.market_policy.field.royalty'),
            ],
        ];
    }
}
