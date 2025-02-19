<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\DispatchRule;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class DispatchRuleEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchRuleEnum',
            'values' => DispatchRule::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.dispatch_rule.description'),
        ];
    }
}
