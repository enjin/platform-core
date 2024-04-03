<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TransferRecipientInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TransferRecipient',
            'description' => __('enjin-platform::input_type.transfer_recipient.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'account' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::input_type.mint_recipient.field.account'),
                'rules' => ['filled', new ValidSubstrateAccount()],
            ],
            'simpleParams' => [
                'type' => GraphQL::type('SimpleTransferParams'),
            ],
            'operatorParams' => [
                'type' => GraphQL::type('OperatorTransferParams'),
            ],
            'transferBalanceParams' => [
                'type' => GraphQL::type('TransferBalanceParams'),
            ],
        ];
    }
}
