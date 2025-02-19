<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class DispatchInputType extends InputType implements PlatformGraphQlType
{
    /**
     * Get the input type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchInputType',
            'description' => __('enjin-platform::input_type.dispatch.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'call' => [
                'type' => GraphQL::type('DispatchCall!'),
                'description' => __('enjin-platform::enum.dispatch_call.description'),
            ],
            'query' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::input_type.dispatch.field.query'),
            ],
            'variables' => [
                'type' => GraphQL::type('Object'),
                'description' => __('enjin-platform::input_type.dispatch.field.variables'),
            ],
            'settings' => [
                'type' => GraphQL::type('DispatchSettingsInputType'),
                'description' => __('enjin-platform::input_type.dispatch.field.settings'),
            ],
        ];
    }
}
