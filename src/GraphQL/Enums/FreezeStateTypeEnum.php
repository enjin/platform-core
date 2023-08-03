<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class FreezeStateTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'FreezeStateType',
            'values' => FreezeStateType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.freeze_state_type.description'),
        ];
    }
}
