<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenAccountType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenAccount',
            'description' => __('enjin-platform::type.token_account.description'),
            'model' => TokenAccount::class,
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
            'balance' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token_account.field.balance'),
            ],
            'reservedBalance' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token_account.field.reservedBalance'),
                'alias' => 'reserved_balance',
            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token_account.field.isFrozen'),
                'alias' => 'is_frozen',
            ],

            // Related
            'collection' => [
                'type' => GraphQL::type('Collection!'),
                'description' => __('enjin-platform::type.token_account.field.collection'),
                'is_relation' => true,
            ],
            'wallet' => [
                'type' => GraphQL::type('Wallet'),
                'description' => __('enjin-platform::type.token_account.field.wallet'),
                'is_relation' => true,
            ],
            'token' => [
                'type' => GraphQL::type('Token!'),
                'description' => __('enjin-platform::type.token_account.field.token'),
                'is_relation' => true,
            ],
            'approvals' => [
                'type' => GraphQL::type('[TokenAccountApproval]'),
                'description' => __('enjin-platform::type.collection_account.field.approvals'),
                'is_relation' => true,
            ],
            'namedReserves' => [
                'type' => GraphQL::type('[TokenAccountNamedReserve]'),
                'description' => __('enjin-platform::type.collection_account.field.namedReserves'),
                'is_relation' => true,
            ],
        ];
    }
}
