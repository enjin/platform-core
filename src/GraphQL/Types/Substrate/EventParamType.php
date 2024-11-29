<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class EventParamType extends GraphQLType implements PlatformGraphQlType
{
    use InGlobalSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'EventParam',
            'description' => __('enjin-platform::type.event_param.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'type' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.event_param.field.type'),
            ],
            'value' => [
                'type' => GraphQL::type('Json'),
                'description' => __('enjin-platform::type.event_param.field.value'),
            ],
        ];
    }
}
