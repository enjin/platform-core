<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Type;

class MarketplaceBidType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InMarketplaceSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'MarketplaceBid',
            'description' => __('enjin-platform-marketplace::type.marketplace_bid.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
            ],
            'height' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_state.field.height'),
                'alias' => 'height',
            ],
            'bidder' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.bidder'),
                'is_relation' => true,
            ],
            'listing' => [
                'type' => GraphQL::type('MarketplaceListing!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.description'),
                'is_relation' => true,
            ],
        ];
    }
}
