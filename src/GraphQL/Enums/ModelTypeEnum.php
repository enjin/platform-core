<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Global\FilterType;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class ModelTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'ModelType',
            'values' => ModelType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.model_type.description'),
        ];
    }
}
