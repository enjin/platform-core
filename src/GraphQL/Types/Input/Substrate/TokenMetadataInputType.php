<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\StringMaxByteLength;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TokenMetadataInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenMetadataInput',
            'description' => __('enjin-platform::type.metadata_input.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'name' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::input_type.metadata.field.name'),
                'rules' => ['bail', 'nullable', new StringMaxByteLength(32)],
            ],
            'symbol' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::input_type.metadata.field.symbol'),
                'rules' => ['bail', 'nullable', new StringMaxByteLength(8)],
            ],
            'decimalCount' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::input_type.metadata.field.decimalCount'),
                'defaultValue' => 0,
                'rules' => ['bail', 'nullable', 'integer', 'min:0', 'max:18'],
            ],
        ];
    }
}
