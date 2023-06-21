<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TokenMarketBehaviorInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenMarketBehaviorInput',
            'description' => __('enjin-platform::input_type.token_market_behavior.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'hasRoyalty' => [
                'type' => GraphQL::type('RoyaltyInput'),
                'description' => __('enjin-platform::input_type.mutation_royalty.field.beneficiary'),
            ],
            'isCurrency' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.mutation_royalty.field.isCurrency'),
            ],
        ];
    }
}
