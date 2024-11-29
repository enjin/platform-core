<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class BalancesType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'Balances',
            'description' => __('enjin-platform::type.balances.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'free' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.balances.free'),
            ],
            'reserved' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.balances.reserved'),
            ],
            'miscFrozen' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.balances.miscFrozen'),
            ],
            'feeFrozen' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.balances.feeFrozen'),
            ],
        ];
    }
}
