<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\TokenMarketBehavior;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class TokenMarketBehaviorTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenMarketBehaviorType',
            'values' => TokenMarketBehavior::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.token_market_behavior_type.description'),
        ];
    }
}
