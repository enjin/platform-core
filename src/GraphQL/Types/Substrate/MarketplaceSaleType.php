<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\Models\Sale;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Type;

class MarketplaceSaleType extends Type implements PlatformGraphQlType
{
    use InMarketplaceSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'MarketplaceSale',
            'description' => __('enjin-platform-marketplace::type.marketplace_sale.description'),
            'model' => Sale::class,
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
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.amount'),
            ],

            // Relationships
            'bidder' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.bidder'),
                'alias' => 'buyer',
            ],
            'buyer' => [
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
