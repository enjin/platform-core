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
            // Computed
            'key' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.key'),
                'alias' => 'key_string',
            ],
            'value' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.value'),
                'alias' => 'value_string',
            ],
        ];
    }
}
