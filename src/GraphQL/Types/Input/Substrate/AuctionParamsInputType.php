<?php

namespace Enjin\Platform\Marketplace\GraphQL\Types\Input;

use Enjin\Platform\Marketplace\GraphQL\Types\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AuctionParamsInputType extends InputType
{
    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'AuctionParamsInput',
            'description' => __('enjin-platform-marketplace::type.auction_data.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'startBlock' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-marketplace::type.auction_data.field.startBlock'),
            ],
            'endBlock' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-marketplace::type.auction_data.field.endBlock'),
            ],
        ];
    }
}
