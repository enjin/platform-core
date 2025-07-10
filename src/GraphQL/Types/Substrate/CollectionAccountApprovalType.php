<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Arr;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\Account;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class CollectionAccountApprovalType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'CollectionAccountApproval',
            'description' => __('enjin-platform::type.collection_account_approval.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            // Properties
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.collection_account_approval.field.expiration'),
            ],
            'wallet' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform::type.collection_account_approval.field.wallet'),
                'is_relation' => true,
                'resolve' => fn ($approval) => Account::firstWhere('id', Arr::get($approval, 'accountId')),
            ],

            // Related
            //            'account' => [
            //                'type' => GraphQL::type('CollectionAccount!'),
            //                'description' => __('enjin-platform::type.collection_account_approval.field.account'),
            //                'is_relation' => true,
            //            ],

        ];
    }
}
