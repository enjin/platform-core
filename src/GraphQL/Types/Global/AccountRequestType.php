<?php

namespace Enjin\Platform\GraphQL\Types\Global;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class AccountRequestType extends Type implements PlatformGraphQlType
{
    use InGlobalSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'AccountRequest',
            'description' => __('enjin-platform::type.account_request.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'qrCode' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.account_request.field.qrCode'),
            ],
            'proofUrl' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.account_request.field.proofUrl'),
            ],
            'proofCode' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.account_request.field.proofCode'),
            ],
            'verificationId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.account_request.field.verificationId'),
            ],
        ];
    }
}
