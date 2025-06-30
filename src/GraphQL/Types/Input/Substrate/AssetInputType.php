<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AssetInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'AssetInputType',
            'description' => __('enjin-platform-marketplace::input_type.asset.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::input_type.asset.field.collectionId'),
            ],
            'tokenId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::input_type.asset.field.tokenId'),
            ],
        ];
    }
}
