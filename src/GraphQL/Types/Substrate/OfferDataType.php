<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Type;

class OfferDataType extends Type implements PlatformGraphQlType
{
    use InMarketplaceSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'OfferData',
            'description' => __('enjin-platform-marketplace::type.offer_data.description'),
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
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform-marketplace::type.offer_data.field.expiration'),
                'alias' => 'offer_expiration',
            ],
        ];
    }
}
