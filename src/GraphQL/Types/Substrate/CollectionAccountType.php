<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class CollectionAccountType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'CollectionAccount',
            'description' => __('enjin-platform::type.collection_account.description'),
            'model' => CollectionAccount::class,
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
            'accountCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.collection_account.field.accountCount'),
                'alias' => 'account_count',
            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.collection_account.field.isFrozen'),
                'alias' => 'is_frozen',
            ],
            'approvals' => [
                'type' => GraphQL::type('[CollectionAccountApproval]'),
                'description' => __('enjin-platform::type.collection_account.field.approvals'),
                'is_relation' => false,
            ],

            //
            //            // Related
            //            'collection' => [
            //                'type' => GraphQL::type('Collection!'),
            //                'description' => __('enjin-platform::type.collection_account.field.collection'),
            //                'is_relation' => true,
            //            ],
            //            'wallet' => [
            //                'type' => GraphQL::type('Wallet'),
            //                'description' => __('enjin-platform::type.collection_account.field.wallet'),
            //                'is_relation' => true,
            //            ],
        ];
    }
}
