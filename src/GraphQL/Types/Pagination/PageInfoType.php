<?php

namespace Enjin\Platform\GraphQL\Types\Pagination;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class PageInfoType extends Type implements PlatformGraphQlType
{
    use InGlobalSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'PageInfo',
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'hasNextPage' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.page_info.field.hasNextPage'),
                'selectable' => false,
            ],
            'hasPreviousPage' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.page_info.field.hasPreviousPage'),
                'selectable' => false,
            ],
            'startCursor' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.page_info.field.startCursor'),
                'selectable' => false,
            ],
            'endCursor' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.page_info.field.endCursor'),
                'selectable' => false,
            ],
        ];
    }
}
