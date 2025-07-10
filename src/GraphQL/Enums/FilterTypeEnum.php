<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Global\FilterType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Override;
use Rebing\GraphQL\Support\EnumType;

class FilterTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'FilterType',
            'values' => FilterType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.filter_type.description'),
        ];
    }
}
