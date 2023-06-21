<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Global\TokenType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class TokenTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenType',
            'values' => TokenType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.token_type.description'),
        ];
    }
}
