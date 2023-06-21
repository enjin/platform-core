<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQlType;

class AccountType extends GraphQlType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Account',
            'description' => __('enjin-platform::type.account.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'publicKey' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.account.field.publicKey'),
            ],
            'address' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.account.field.address'),
            ],
        ];
    }
}
