<?php

namespace Enjin\Platform\GraphQL\Types\Input\Global;

use Enjin\Platform\Enums\Global\FilterType;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class StringFilterInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'StringFilter',
            'description' => __('enjin-platform::type.string_filter_input.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'type' => [
                'type' => GraphQL::type('FilterType'),
                'description' => __('enjin-platform::type.string_filter_input.type.description'),
                'defaultValue' => FilterType::AND->value,
            ],
            'filter' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.string_filter_input.filter.description'),
            ],
        ];
    }
}
