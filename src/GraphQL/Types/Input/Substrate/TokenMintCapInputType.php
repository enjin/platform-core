<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TokenMintCapInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenMintCap',
            'description' => __('enjin-platform::input_type.token_mint_cap.description'),
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
                'type' => GraphQL::type('TokenMintCapType!'),
                'description' => __('enjin-platform::input_type.token_mint_cap.field.type'),
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.token_mint_cap.field.amount'),
                'defaultValue' => null,
            ],
        ];
    }
}
