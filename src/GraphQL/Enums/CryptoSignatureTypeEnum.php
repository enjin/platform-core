<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\CryptoSignatureType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class CryptoSignatureTypeEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'CryptoSignatureType',
            'values' => CryptoSignatureType::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.crypto_signature.description'),
        ];
    }
}
