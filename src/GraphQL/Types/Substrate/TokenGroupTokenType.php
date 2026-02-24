<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\TokenGroupToken;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenGroupTokenType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenGroupToken',
            'description' => __('enjin-platform::type.token_group_token.description'),
            'model' => TokenGroupToken::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'token' => [
                'type' => GraphQL::type('Token!'),
                'description' => __('enjin-platform::type.token_group_token.field.token'),
                'is_relation' => true,
            ],
            'tokenGroup' => [
                'type' => GraphQL::type('TokenGroup!'),
                'description' => __('enjin-platform::type.token_group_token.field.tokenGroup'),
                'is_relation' => true,
            ],
            'position' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.token_group_token.field.position'),
            ],
        ];
    }
}
