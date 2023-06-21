<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class TransactionResultEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TransactionResult',
            'values' => [
                SystemEventType::EXTRINSIC_SUCCESS->name,
                SystemEventType::EXTRINSIC_FAILED->name,
            ],
            'description' => __('enjin-platform::enum.transaction_result.description'),
        ];
    }
}
