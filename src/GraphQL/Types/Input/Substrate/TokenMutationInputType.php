<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TokenMutationInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenMutationInput',
            'description' => __('enjin-platform::input_type.token_mutation.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'behavior' => [
                'type' => GraphQL::type('TokenMarketBehaviorInput'),
                'description' => __('enjin-platform::input_type.token_mutation.field.behavior'),
            ],
            'listingForbidden' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.token_mutation.field.listingForbidden'),
            ],
            'anyoneCanInfuse' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.token.field.anyoneCanInfuse'),
            ],
            'name' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.token.field.name'),
            ],
        ];
    }
}
