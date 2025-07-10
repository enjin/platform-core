<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\Bid;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class MarketplaceBidType extends Type implements PlatformGraphQlType
{
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
            'model' => Bid::class,
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
            ],
            'height' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_state.field.height'),
            ],

            // Relationship
            'bidder' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.bidder'),
            ],
            'listing' => [
                'type' => GraphQL::type('MarketplaceListing!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.description'),
            ],
        ];
    }
}
