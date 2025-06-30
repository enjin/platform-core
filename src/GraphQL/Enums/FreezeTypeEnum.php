<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Override;
use Rebing\GraphQL\Support\EnumType;

class FreezeTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'FreezeType',
            'values' => FreezeType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.freezable_type.description'),
        ];
    }
}
