<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries\GetTransactionsQuery;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Traits\HasSelectFields;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQlType;

class WalletType extends GraphQlType implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Create new wallet type instance.
     */
    public function __construct(
        protected BlockchainServiceInterface $blockchainService
    ) {}

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'Wallet',
            'description' => __('enjin-platform::type.wallet.description'),
            'model' => Wallet::class,
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
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.wallet.field.id'),
            ],
            'account' => [
                'type' => GraphQL::type('Account'),
                'description' => __('enjin-platform::type.wallet.field.account'),
                'resolve' => fn ($wallet) => [
                    'publicKey' => $wallet->public_key,
                    'address' => $wallet->address,
                ],
                'is_relation' => false,
                'selectable' => false,
                'always' => ['public_key'],
            ],
            'externalId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.wallet.field.externalId'),
                'alias' => 'external_id',
            ],
            'managed' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.wallet.field.managed'),
            ],
            'network' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.wallet.field.network'),
            ],

            // Related
            'nonce' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.wallet.field.nonce'),
                'resolve' => fn ($wallet) => $this->blockchainService->walletWithBalanceAndNonce($wallet)->nonce,
                'selectable' => false,
            ],
            'balances' => [
                'type' => GraphQL::type('Balances'),
                'description' => __('enjin-platform::type.wallet.field.balances'),
                'resolve' => fn ($wallet) => $this->blockchainService->walletWithBalanceAndNonce($wallet)->balances,
                'selectable' => false,
                'is_relation' => false,
            ],
            'collectionAccounts' => [
                'type' => GraphQL::paginate('CollectionAccount', 'CollectionAccountConnection'),
                'description' => __('enjin-platform::type.wallet.field.collectionAccounts'),
                'args' => ConnectionInput::args([
                    'collectionIds' => [
                        'type' => GraphQL::type('[BigInt]'),
                        'description' => __('enjin-platform::type.wallet.field.collectionIds'),
                    ],
                ]),
                'resolve' => fn ($wallet, $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->collectionAccounts,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->collection_accounts_count,
                ],
                'is_relation' => true,
            ],
            'tokenAccounts' => [
                'type' => GraphQL::paginate('TokenAccount', 'TokenAccountConnection'),
                'description' => __('enjin-platform::type.wallet.field.tokenAccounts'),
                'args' => ConnectionInput::args([
                    'collectionIds' => [
                        'type' => GraphQL::type('[BigInt]'),
                        'description' => __('enjin-platform::query.get_tokens.args.collectionId'),
                    ],
                    'tokenIds' => [
                        'type' => GraphQL::type('[BigInt]'),
                        'description' => __('enjin-platform::query.get_tokens.args.tokenIds'),
                    ],
                    'bulkFilter' => [
                        'type' => GraphQL::type('[TokenFilterInput!]'),
                        'description' => __('enjin-platform::input_type.multi_token_id.description'),
                    ],
                ]),
                'resolve' => fn ($wallet, $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->tokenAccounts,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->token_accounts_count,
                ],
                'is_relation' => true,
            ],
            'collectionAccountApprovals' => [
                'type' => GraphQL::paginate('CollectionAccountApproval', 'CollectionAccountApprovalConnection'),
                'description' => __('enjin-platform::type.wallet.field.collectionAccountApprovals'),
                'args' => ConnectionInput::args(),
                'resolve' => fn ($wallet, $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->collectionAccountApprovals,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->collection_account_approvals_count,
                ],
                'is_relation' => true,
            ],
            'tokenAccountApprovals' => [
                'type' => GraphQL::paginate('TokenAccountApproval', 'TokenAccountApprovalConnection'),
                'description' => __('enjin-platform::type.wallet.field.tokenAccountApprovals'),
                'args' => ConnectionInput::args(),
                'resolve' => fn ($wallet, $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->tokenAccountApprovals,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->token_account_approvals_count,
                ],
                'is_relation' => true,
            ],
            'transactions' => [
                'type' => GraphQL::paginate('Transaction', 'TransactionConnection'),
                'description' => __('enjin-platform::type.wallet.field.transactions'),
                'args' => Arr::except(GetTransactionsQuery::resolveArgs(), ['accounts']),
                'resolve' => fn ($wallet, array $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->transactions,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->transactions_count,
                ],
                'is_relation' => true,
            ],
            'ownedCollections' => [
                'type' => GraphQL::paginate('Collection', 'CollectionConnection'),
                'description' => __('enjin-platform::type.wallet.field.ownedCollections'),
                'args' => ConnectionInput::args([
                    'collectionIds' => [
                        'type' => GraphQL::type('[BigInt]'),
                        'description' => __('enjin-platform::type.wallet.field.collectionIds'),
                    ],
                ]),
                'resolve' => fn ($wallet, $args) => [
                    'items' => new CursorPaginator(
                        $wallet?->ownedCollections,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $wallet?->owned_collections_count,
                ],
                'is_relation' => true,
            ],
        ];
    }
}
