<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\StringMaxByteLength;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class AttributeInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'AttributeInput',
            'description' => __('enjin-platform::type.attribute_input.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'key' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.key'),
                'rules' => ['filled', 'alpha_dash', new StringMaxByteLength(256)],
            ],
            'value' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.value'),
                'rules' => ['filled', new StringMaxByteLength(1024)],
            ],
        ];
    }
}
