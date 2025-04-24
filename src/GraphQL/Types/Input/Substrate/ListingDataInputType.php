<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ListingDataInputType extends InputType
{
    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ListingDataInput',
            'description' => __('enjin-platform-marketplace::type.listing_data.description'),
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
                'description' => __('enjin-platform-marketplace::type.listing_data.field.type'),
            ],
            'auctionParams' => [
                'type' => GraphQL::type('AuctionParamsInput'),
                'description' => __('enjin-platform-marketplace::type.listing_data.field.auctionParams'),
            ],
            'offerParams' => [
                'type' => GraphQL::type('OfferParamsInput'),
                'description' => __('enjin-platform-marketplace::type.listing_data.field.offerParams'),
            ],
        ];
    }
}
