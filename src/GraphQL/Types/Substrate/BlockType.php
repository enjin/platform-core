<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\Block;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class BlockType extends GraphQLType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'Block',
            'description' => __('enjin-platform::type.block.description'),
            'model' => Block::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.block.field.id'),
            ],
            'number' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.block.field.number'),
                'alias' => 'block_number',
                'selectable' => true,
            ],
            'hash' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.block.field.hash'),
                'alias' => 'block_hash',
            ],

            // Deprecated
            'synced' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.block.field.synced'),
                'deprecationReason' => '',
                'selectable' => false,
                'resolve' => fn () => true,
            ],
            'failed' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.block.field.failed'),
                'deprecationReason' => '',
                'selectable' => false,
                'resolve' => fn () => false,
            ],
            'exception' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.block.field.exception'),
                'deprecationReason' => '',
                'selectable' => false,
                'resolve' => fn () => null,
            ],
        ];
    }
}
