<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenMetadataType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenMetadata',
            'description' => __('enjin-platform::type.token_metadata.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'name' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.token_metadata.field.name'),
            ],
            'symbol' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.token_metadata.field.symbol'),
            ],
            'decimalCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.token_metadata.field.decimal_count'),
            ],
        ];
    }
}
