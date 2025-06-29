<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class CollectionType extends Type implements PlatformGraphQlType
{
    use HasEncodableTokenId;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
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
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.collection_type.field.collectionId'),
            ],
            'maxTokenCount' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenCount'),
                'alias' => 'mint_policy',
                'resolve' => fn ($c) => Arr::get($c->mint_policy, 'maxTokenCount'),
            ],
            'maxTokenSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenSupply'),
                'alias' => 'mint_policy',
                'resolve' => fn ($c) => Arr::get($c->mint_policy, 'maxTokenSupply'),
            ],
            'forceCollapsingSupply' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.collection_type.field.forceCollapsingSupply'),
                'alias' => 'mint_policy',
                'resolve' => fn ($c) => Arr::get($c->mint_policy, 'forceSingleMint', false),
            ],
            'frozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.collection_type.field.frozen'),
                'alias' => 'transfer_policy',
                'resolve' => fn ($c) => Arr::get($c->transfer_policy, 'isFrozen', false),
            ],
            'royalty' => [
                'type' => GraphQL::type('Royalty'),
                'description' => __('enjin-platform::type.collection_type.field.royalty'),
                'alias' => 'market_policy',
                'is_relation' => false,
                'resolve' => function ($c): ?array {
                    if (empty($beneficiary = Arr::get($c->market_policy, 'beneficiaries.0'))) {
                        return null;
                    }

                    $wallet = Wallet::firstWhere('id', Arr::get($beneficiary, 'accountId'));
                    if (!$wallet) {
                        return null;
                    }

                    return [
                        'beneficiary' => $wallet,
                        'percentage' => Arr::get($beneficiary, 'percentage'),
                    ];
                },
            ],
            'totalDeposit' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.collection_type.field.totalDeposit'),
                'alias' => 'total_deposit',
            ],
            'network' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.collection_type.field.network'),
                'selectable' => false,
                'resolve' => fn () => currentMatrix()->name,
            ],

            // Relationships
            'owner' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform::type.collection_type.field.owner'),
            ],
            'attributes' => [
                'type' => GraphQL::type('[Attribute]'),
                'description' => __('enjin-platform::type.collection_type.field.attributes'),
            ],
            'accounts' => [
                'type' => GraphQL::paginate('CollectionAccount', 'CollectionAccountConnection'),
                'description' => __('enjin-platform::type.collection_type.field.accounts'),
                'args' => ConnectionInput::args(),
                'alias' => 'collectionAccounts',
                'resolve' => fn ($c, $args) => $c?->collectionAccounts()->cursorPaginateWithTotal('id', $args['first']),
            ],
            'tokens' => [
                'type' => GraphQL::paginate('Token', 'TokenConnection'),
                'description' => __('enjin-platform::type.collection_type.field.tokens'),
                'args' => ConnectionInput::args([
                    'ids' => [
                        'type' => GraphQL::type('[String]'),
                        'description' => '',
                        'rules' => ['array', 'max:100'],
                    ],
                    'tokenIds' => [
                        'type' => GraphQL::type('[EncodableTokenIdInput]'),
                        'description' => __('enjin-platform::query.get_tokens.args.tokenIds'),
                        'rules' => ['array', 'max:100'],
                    ],
                ]),
                'resolve' => fn ($c, $args) => $c?->tokens()
                    ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('id', $args['ids']))
                    ->when(!empty($args['tokenIds']), fn (Builder $query, $tokenIds) => $query->whereIn('token_id', collect($args['tokenIds'])->map(fn ($tokenId) => $this->encodeTokenId(['tokenId' => $tokenId]))->all()))
                    ->cursorPaginateWithTotal('id', $args['first']),
            ],

            // Deprecated
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.collection_type.field.collectionId'),
                'deprecationReason' => '',
                'alias' => 'collection_id',
            ],
            'forceSingleMint' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.collection_type.field.forceSingleMint'),
                'deprecationReason' => __('enjin-platform::deprecated.collection_type.field.forceSingleMint'),
                'alias' => 'mint_policy',
                'resolve' => fn ($c) => Arr::get($c->mint_policy, 'forceSingleMint'),
            ],
            'totalInfusion' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.collection_type.field.totalInfusion'),
                'deprecationReason' => '',
                'selectable' => false,
                'resolve' => fn () => 0,
            ],
            'creationDeposit' => [
                'type' => GraphQL::type('CreationDeposit!'),
                'description' => __('enjin-platform::type.collection_type.field.creationDeposit'),
                'selectable' => false,
                'is_relation' => false,
                'resolve' => fn () => [
                    'depositor' => Wallet::first(),
                    'amount' => 0,
                ],
            ],
        ];
    }
}
