<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Traits\HasSelectFields;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class CollectionType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Collection',
            'description' => __('enjin-platform::type.collection.description'),
            'model' => Collection::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.collection_type.field.collectionId'),
                'alias' => 'collection_chain_id',
            ],
            'maxTokenCount' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenCount'),
                'alias' => 'max_token_count',
            ],
            'maxTokenSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenSupply'),
                'alias' => 'max_token_supply',
            ],
            'forceSingleMint' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.collection_type.field.forceSingleMint'),
                'alias' => 'force_single_mint',
            ],
            'frozen' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.collection_type.field.frozen'),
                'alias' => 'is_frozen',
            ],
            'royalty' => [
                'type' => GraphQL::type('Royalty'),
                'description' => __('enjin-platform::type.collection_type.field.royalty'),
                'resolve' => function ($collection) {
                    if ($collection->royaltyBeneficiary === null) {
                        return;
                    }

                    return [
                        'beneficiary' => $collection->royaltyBeneficiary,
                        'percentage' => $collection->royalty_percentage,
                    ];
                },
                'is_relation' => false,
                'selectable' => false,
                'always' => ['royalty_wallet_id', 'royalty_percentage'],
            ],
            'network' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.collection_type.field.network'),
            ],

            // Related
            'owner' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform::type.collection_type.field.owner'),
                'is_relation' => true,
            ],
            'attributes' => [
                'type' => GraphQL::type('[Attribute]'),
                'description' => __('enjin-platform::type.collection_type.field.attributes'),
                'is_relation' => true,
            ],
            'accounts' => [
                'type' => GraphQL::paginate('CollectionAccount', 'CollectionAccountConnection'),
                'description' => __('enjin-platform::type.collection_type.field.accounts'),
                'args' => ConnectionInput::args(),
                'resolve' => fn ($collection, $args, $context, $info) => [
                    'items' => new CursorPaginator(
                        $collection?->accounts,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $collection?->accounts_count,
                ],
                'is_relation' => true,
            ],
            'tokens' => [
                'type' => GraphQL::paginate('Token', 'TokenConnection'),
                'description' => __('enjin-platform::type.collection_type.field.tokens'),
                'args' => ConnectionInput::args(),
                'resolve' => fn ($collection, $args) => [
                    'items' => new CursorPaginator(
                        $collection?->tokens,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $collection?->tokens_count,
                ],
                'is_relation' => true,
            ],
        ];
    }
}
