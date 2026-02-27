<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenGroupType extends Type implements PlatformGraphQlType
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
            'name' => 'TokenGroup',
            'description' => __('enjin-platform::type.token_group.description'),
            'model' => TokenGroup::class,
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
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token_group.field.id'),
                'alias' => 'token_group_chain_id',
            ],
            'collection' => [
                'type' => GraphQL::type('Collection!'),
                'description' => __('enjin-platform::type.token_group.field.collection'),
                'is_relation' => true,
            ],
            'attributes' => [
                'type' => GraphQL::type('[Attribute]'),
                'description' => __('enjin-platform::type.token_group.field.attributes'),
                'is_relation' => true,
            ],
            'tokens' => [
                'type' => GraphQL::type('[TokenGroupToken]'),
                'description' => __('enjin-platform::type.token_group.field.tokens'),
                'alias' => 'tokenGroupTokens',
                'is_relation' => true,
            ],

            // Computed
            'metadata' => [
                'type' => GraphQL::type('Object'),
                'description' => __('enjin-platform::type.token_group.field.metadata'),
                'selectable' => false,
            ],
        ];
    }
}
