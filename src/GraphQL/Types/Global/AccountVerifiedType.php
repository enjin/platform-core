<?php

namespace Enjin\Platform\GraphQL\Types\Global;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class AccountVerifiedType extends Type implements PlatformGraphQlType
{
    use InGlobalSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'AccountVerified',
            'description' => __('enjin-platform::type.account_verified.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'verified' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.account_verified.field.verified'),
            ],
            'account' => [
                'type' => GraphQL::type('Account'),
                'description' => __('enjin-platform::type.account_verified.field.account'),
            ],
        ];
    }
}
