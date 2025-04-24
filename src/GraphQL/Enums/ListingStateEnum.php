<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class ListingStateEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ListingStateEnum',
            'values' => ListingState::caseNamesAsArray(),
            'description' => __('enjin-platform-marketplace::enum.listing_state.description'),
        ];
    }
}
