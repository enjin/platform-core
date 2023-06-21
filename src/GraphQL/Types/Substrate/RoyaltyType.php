<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class RoyaltyType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Royalty',
            'description' => __('enjin-platform::type.royalty.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'beneficiary' => [
                'type' => GraphQL::type('Wallet!'),
            ],
            'percentage' => [
                'type' => GraphQL::type('Float!'),
            ],
        ];
    }
}
