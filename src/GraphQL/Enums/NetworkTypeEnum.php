<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Global\NetworkType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class NetworkTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'NetworkType',
            'values' => NetworkType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.network_type.description'),
        ];
    }
}
