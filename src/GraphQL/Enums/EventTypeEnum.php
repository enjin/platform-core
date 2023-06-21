<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\MultiTokensEventType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class EventTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'EventType',
            'values' => MultiTokensEventType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.event_type.description'),
        ];
    }
}
