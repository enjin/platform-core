<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\CoveragePolicy;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class CoveragePolicyEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'CoveragePolicy',
            'values' => CoveragePolicy::caseNamesAsArray(),
            'description' => __('enjin-platform-fuel-tanks::enum.coverage_policy.description'),
        ];
    }
}
