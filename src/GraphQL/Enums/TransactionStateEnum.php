<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Rebing\GraphQL\Support\EnumType;

class TransactionStateEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TransactionState',
            'values' => TransactionState::caseNamesAsArray(),
            'description' => __('enjin-platform::enum.transaction_state.description'),
        ];
    }
}
