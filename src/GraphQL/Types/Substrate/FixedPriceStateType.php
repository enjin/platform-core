<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Type;

class FixedPriceStateType extends Type implements PlatformGraphQlType
{
    use InMarketplaceSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'FixedPriceState',
            'description' => __('enjin-platform-marketplace::type.fixed_price_state.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'type' => [
                'type' => GraphQL::type('ListingType!'),
                'description' => __('enjin-platform-marketplace::enum.listing_type.description'),
            ],
            'amountFilled' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform-marketplace::type.fixed_price_state.field.amountFilled'),
                'alias' => 'amount_filled',
            ],
        ];
    }
}
