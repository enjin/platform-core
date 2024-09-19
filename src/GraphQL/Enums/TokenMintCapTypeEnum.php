<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class TokenMintCapTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenMintCapType',
            'values' => [...TokenMintCapType::caseNamesAsArray(), 'SINGLE_MINT', 'INFINITE'],
            'description' => __('enjin-platform::enum.token_mint_cap_type.description'),
            'deprecationReason' => __('enjin-platform::deprecated.token_mint_cap_type.description'),
        ];
    }
}
