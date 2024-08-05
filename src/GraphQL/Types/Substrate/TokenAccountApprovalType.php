<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenAccountApprovalType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TokenAccountApproval',
            'description' => __('enjin-platform::type.token_account_approval.description'),
            'model' => TokenAccount::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token_account_approval.args.amount'),
            ],
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.collection_account_approval.field.expiration'),
            ],

            // Related
            'account' => [
                'type' => GraphQL::type('TokenAccount!'),
                'description' => __('enjin-platform::type.token_account_approval.field.account'),
                'is_relation' => true,
            ],
            'wallet' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform::type.collection_account_approval.field.wallet'),
                'is_relation' => true,
            ],
        ];
    }
}
