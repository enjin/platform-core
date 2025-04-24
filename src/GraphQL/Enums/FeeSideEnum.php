<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\FeeSide;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class FeeSideEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'FeeSide',
            'values' => FeeSide::caseNamesAsArray(),
            'description' => __('enjin-platform-marketplace::enum.fee_side.description'),
        ];
    }
}