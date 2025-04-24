<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\ListingType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class ListingTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ListingType',
            'values' => ListingType::caseNamesAsArray(),
            'description' => __('enjin-platform-marketplace::enum.listing_type.description'),
        ];
    }
}
