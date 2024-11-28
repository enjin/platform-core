<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Block;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class BlockType extends GraphQLType implements PlatformGraphQlType
{
    use HasSelectFields;
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
            'id' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.block.field.id'),
            ],
            'number' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.block.field.number'),
            ],
            'hash' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.block.field.hash'),
            ],
            'synced' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.block.field.synced'),
            ],
            'failed' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.block.field.failed'),
            ],
            'exception' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.block.field.exception'),
            ],
        ];
    }
}
