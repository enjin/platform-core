<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Attribute;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class AttributeType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Attribute',
            'description' => __('enjin-platform::type.attribute.description'),
            'model' => Attribute::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'key' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.key'),
            ],
            'value' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.value'),
                'resolve' => function ($attribute) {
                    if (strtolower($attribute->key) == 'uri' && strpos($attribute->value, '{id}') !== false && $attribute->token_id) {
                        return str_replace('{id}', "{$attribute->token->collection->collection_chain_id}-{$attribute->token->token_chain_id}", $attribute->value);
                    }

                    return $attribute->value;
                },
            ],
        ];
    }
}
