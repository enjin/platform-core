<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class PalletIdentifierEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'PalletIdentifier',
            'values' => PalletIdentifier::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.pallet_identifier.description'),
        ];
    }
}
