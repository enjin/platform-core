<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\DispatchCall;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class DispatchCallEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchCall',
            'values' => DispatchCall::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.dispatch_call.description'),
        ];
    }
}
