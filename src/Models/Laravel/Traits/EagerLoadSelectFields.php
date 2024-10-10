<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\GraphQL\Types\Global\PendingEventType;
use Enjin\Platform\GraphQL\Types\Substrate\BlockType;
use Enjin\Platform\GraphQL\Types\Substrate\CollectionAccountApprovalType;
use Enjin\Platform\GraphQL\Types\Substrate\CollectionAccountType;
use Enjin\Platform\GraphQL\Types\Substrate\CollectionType;
use Enjin\Platform\GraphQL\Types\Substrate\EventType;
use Enjin\Platform\GraphQL\Types\Substrate\TokenAccountApprovalType;
use Enjin\Platform\GraphQL\Types\Substrate\TokenAccountNamedReserveType;
use Enjin\Platform\GraphQL\Types\Substrate\TokenAccountType;
use Enjin\Platform\GraphQL\Types\Substrate\TokenType;
use Enjin\Platform\GraphQL\Types\Substrate\TransactionType;
use Enjin\Platform\GraphQL\Types\Substrate\WalletType;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Facades\Enjin\Platform\Services\Database\WalletService;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait EagerLoadSelectFields
{
    /**
     * The query name.
     */
    protected static string $query;

    /**
     * Lazy load relations.
     */
    public static function lazyLoadSelectFields(?Model $model, ResolveInfo $resolveInfo, string $query = 'GetTransaction'): ?Model
    {
        if (!$model) {
            return null;
        }

        if (!isset($model->idempotency_key)) {
            return $model;
        }

        [, $with, $withCount] = static::selectFields($resolveInfo, $query);

        return $model->load($with)->loadCount($withCount);
    }

    /**
     * Load collection's select and relationship fields.
     */
    public static function loadCollection(
        array $selections,
        string $attribute,
        array $args = [],
        ?string $key = null,
        bool $isParent = false
    ): array {
        $fields = static::$query == 'GetTokens' && !isset($selections['collection'])
            ? []
            : Arr::get($selections, $attribute, $selections);
        $hasBeneficiary = (bool) Arr::get($fields, 'royalty.fields.beneficiary');
        $hasCreationDepositor = (bool) Arr::get($fields, 'creationDeposit.fields.depositor');
        $select = array_filter([
            'id',
            'collection_chain_id',
            'max_token_supply',
            'force_collapsing_supply',
            isset($fields['owner']) || static::$query == 'GetWallet' ? 'owner_wallet_id' : null,
            $hasBeneficiary ? 'royalty_wallet_id' : null,
            $hasCreationDepositor ? 'creation_depositor' : null,
            Arr::get($fields, 'royalty.fields.percentage') ? 'royalty_percentage' : null,
            Arr::get($fields, 'creationDeposit.fields.amount') ? 'creation_deposit_amount' : null,
            ...CollectionType::getSelectFields($fieldKeys = array_keys($fields)),
        ]);

        $with = [];
        $withCount = [];

        if (!$isParent) {
            $with = [
                $key => function ($query) use ($select, $args): void {
                    $query->select(array_unique($select))
                        ->when($cursor = Cursor::fromEncoded(Arr::get($args, 'after')), fn ($q) => $q->where('id', '>', $cursor->parameter('id')))
                        ->when(Arr::get($args, 'collectionIds'), fn ($q) => $q->whereIn('collection_chain_id', $args['collectionIds']))
                        ->orderBy('collections.id');
                    // This must be done this way to load eager limit correctly.
                    if ($limit = Arr::get($args, 'first')) {
                        $query->limit($limit + 1);
                    }
                },
            ];
        }

        foreach ([
            ...CollectionType::getRelationFields($fieldKeys),
            ...($hasBeneficiary ? ['royaltyBeneficiary'] : []),
        ] as $relation) {
            if ($isParent && in_array($relation, ['tokens', 'accounts'])) {
                $withCount[] = $relation;
            }

            $with = array_merge(
                $with,
                static::getRelationQuery(
                    CollectionType::class,
                    $relation,
                    $fields,
                    $key,
                    $with
                )
            );
        }

        return [$select, $with, $withCount];
    }

    /**
     * Load token's select and relationship fields.
     */
    public static function loadToken(
        array $selections,
        string $attribute,
        array $args = [],
        ?string $key = null,
        bool $isParent = false
    ): array {
        $fields = Arr::get($selections, $attribute, $selections);
        $hasBeneficiary = (bool) Arr::get($fields, 'royalty.fields.beneficiary');
        $hasDepositor = (bool) Arr::get($fields, 'creationDeposit.fields.depositor');
        $select = array_filter([
            'id',
            'collection_id',
            ...(isset($fields['nonFungible']) ? ['is_currency', 'supply', 'cap', 'cap_supply'] : []),
            $hasBeneficiary ? 'royalty_wallet_id' : null,
            $hasDepositor ? 'creation_depositor' : null,
            Arr::get($fields, 'royalty.fields.percentage') ? 'royalty_percentage' : null,
            Arr::get($fields, 'creationDeposit.fields.amount') ? 'creation_deposit_amount' : null,
            Arr::get($fields, 'tokenMetadata.fields.name') ? 'name' : null,
            Arr::get($fields, 'tokenMetadata.fields.symbol') ? 'symbol' : null,
            Arr::get($fields, 'tokenMetadata.fields.decimalCount') ? 'decimal_count' : null,
            ...TokenType::getSelectFields($fieldKeys = array_keys($fields)),
        ]);

        $with = [];
        $withCount = [];

        if (!$isParent) {
            $with = [
                $key => function ($query) use ($select, $args): void {
                    $query->select(array_unique($select))
                        ->when(
                            Arr::get($args, 'after'),
                            fn ($q) => $q->where('id', '>', Cursor::fromEncoded($args['after'])->parameter('id'))
                        )->orderBy('tokens.id');
                    // This must be done this way to load eager limit correctly.
                    if ($limit = Arr::get($args, 'first')) {
                        $query->limit($limit + 1);
                    }
                },
            ];
        }

        $relations = array_filter([
            isset($fields['nonFungible']) ? 'collection' : null,
            ...(isset($fields['metadata']) ? ['attributes', 'collection'] : []),
            $hasBeneficiary ? 'royaltyBeneficiary' : null,
            ...TokenType::getRelationFields($fieldKeys),
        ]);
        foreach (array_unique($relations) as $relation) {
            if ($relation == 'accounts') {
                $withCount[] = $relation;
            }

            $with = array_merge(
                $with,
                static::getRelationQuery(
                    TokenType::class,
                    $relation,
                    $fields,
                    $key,
                    $with
                )
            );
        }

        return [$select, $with, $withCount];
    }

    /**
     * Load transaction's select and relationship fields.
     */
    public static function loadTransaction(
        array $selections,
        string $attribute,
        array $args = [],
        ?string $key = null,
        bool $isParent = false
    ): array {
        $fields = Arr::get($selections, $attribute, $selections);
        $select = array_filter([
            'id',
            isset($fields['wallet']) || static::$query == 'GetWallet' ? 'wallet_public_key' : null,
            isset($fields['signingPayload']) ? 'encoded_data' : null,
            ...TransactionType::getSelectFields($fieldKeys = array_keys($fields)),
        ]);

        $with = [];
        $withCount = [];

        if (!$isParent) {
            $with = [
                $key => function ($query) use ($select, $args): void {
                    $query->select(array_unique($select))
                        ->when($cursor = Cursor::fromEncoded(Arr::get($args, 'after')), fn ($q) => $q->where('id', '>', $cursor->parameter('id')))
                        ->when(Arr::get($args, 'transactionIds'), fn ($q) => $q->whereIn('transaction_chain_id', $args['transactionIds']))
                        ->when(Arr::get($args, 'transactionHashes'), fn ($q) => $q->whereIn('transaction_chain_hash', $args['transactionHashes']))
                        ->when(Arr::get($args, 'methods'), fn ($q) => $q->whereIn('method', $args['methods']))
                        ->when(Arr::get($args, 'states'), fn ($q) => $q->whereIn('state', $args['states']))
                        ->when(Arr::get($args, 'signedAtBlocks'), fn ($q) => $q->whereIn('signed_at_block', $args['signedAtBlocks']))
                        ->orderBy('transactions.id');

                    // This must be done this way to load eager limit correctly.
                    if ($limit = Arr::get($args, 'first')) {
                        $query->limit($limit + 1);
                    }
                },
            ];
        }

        foreach (TransactionType::getRelationFields($fieldKeys) as $relation) {
            if ($relation == 'events') {
                $withCount[] = $relation;
            }

            $with = array_merge(
                $with,
                static::getRelationQuery(
                    TransactionType::class,
                    $relation,
                    $fields,
                    $key,
                    $with
                )
            );
        }

        return [$select, $with, $withCount];
    }

    /**
     * Load wallet's select and relationship fields.
     */
    public static function loadWallet(
        array $selections,
        string $attribute,
        array $args = [],
        ?string $key = null,
        bool $isParent = false
    ): array {
        $fields = Arr::get($selections, $attribute, $selections);
        $selectedFields = WalletService::processClosures(
            WalletType::getSelectFields($fieldKeys = array_keys($fields))
        );

        $select = array_filter([
            'id',
            'public_key',
            ...$selectedFields,
        ]);

        $with = [];
        $withCount = [];

        if (!$isParent) {
            $with = [
                $key => function ($query) use ($select, $args): void {
                    $query->select($select)
                        ->when($cursor = Cursor::fromEncoded(Arr::get($args, 'after')), fn ($q) => $q->where('id', '>', $cursor->parameter('id')))
                        ->when(Arr::get($args, 'transactionIds'), fn ($q) => $q->whereIn('transaction_chain_id', $args['transactionIds']))
                        ->when(Arr::get($args, 'transactionHashes'), fn ($q) => $q->whereIn('transaction_chain_hash', $args['transactionIds']))
                        ->when(Arr::get($args, 'methods'), fn ($q) => $q->whereIn('method', $args['methods']))
                        ->when(Arr::get($args, 'states'), fn ($q) => $q->whereIn('state', $args['states']))
                        ->orderBy('wallets.id');

                    // This must be done this way to load eager limit correctly.
                    if ($limit = Arr::get($args, 'first')) {
                        $query->limit($limit + 1);
                    }
                },
            ];
        }

        foreach (WalletType::getRelationFields($fieldKeys) as $relation) {
            switch ($relation) {
                case 'collectionAccounts':
                    $withCount[$relation] = fn ($query) => $query->when(
                        Arr::get($args, 'collectionIds'),
                        fn ($q) => $q->whereIn(
                            'collection_id',
                            DB::table('collections')->select('id')->whereIn('collection_chain_id', $args['collectionIds'])
                        )
                    );

                    break;
                case 'tokenAccounts':
                    $args = $args ?: Arr::get($selections, 'tokenAccounts.args');
                    $filters = Arr::get($args, 'bulkFilter');
                    $withCount[$relation] = fn ($query) => $query->when(
                        empty($filters) && Arr::get($args, 'collectionIds'),
                        fn ($q) => $q->whereIn(
                            'collection_id',
                            DB::table('collections')->select('id')->whereIn('collection_chain_id', $args['collectionIds'])
                        )
                    )->when(
                        empty($filters) && Arr::get($args, 'tokenIds'),
                        fn ($q) => $q->whereIn(
                            'token_id',
                            DB::table('tokens')->select('id')->whereIn('token_chain_id', $args['tokenIds'])
                        )
                    )->when(
                        $filters,
                        function ($query) use ($filters): void {
                            $collections = Collection::whereIn('collection_chain_id', collect($filters)->pluck('collectionId'))
                                ->pluck('id', 'collection_chain_id')
                                ->all();

                            $rangeRegx = '/-?[0-9]+(\.\.)-?[0-9]+/';
                            $tokenIds = collect($filters)
                                ->pluck('tokenIds')
                                ->flatten()
                                ->flatMap(function ($token) use ($rangeRegx) {
                                    if (!preg_match($rangeRegx, $token)) {
                                        return [$token];
                                    }

                                    $range = explode('..', $token);

                                    return [$range[0], $range[1]];
                                });

                            $tokens = Token::whereIn('token_chain_id', $tokenIds)
                                ->pluck('id', 'token_chain_id')
                                ->all();

                            foreach ($filters as $filter) {
                                $query->where(function ($subQuery) use ($collections, $tokens, $filter, $rangeRegx): void {
                                    $subQuery->where('collection_id', $collections[$filter['collectionId']]);
                                    $subQuery->where(fn ($sub) => collect($filter['tokenIds'])->each(function ($tokenId) use ($sub, $tokens, $rangeRegx): void {
                                        if (!preg_match($rangeRegx, $tokenId)) {
                                            $sub->orWhere('token_id', $tokens[$tokenId]);
                                        } else {
                                            $range = explode('..', $tokenId);
                                            if (isset($tokens[$range[0]], $tokens[$range[1]])) {
                                                $sub->orWhereBetween('token_id', [(int) $range[0], (int) $range[1]]);
                                            }
                                        }
                                    }));
                                });
                            }

                        },
                    );

                    break;
                case 'transactions':
                    $withCount[$relation] = fn ($query) => $query->when(Arr::get($args, 'transactionIds'), fn ($q) => $q->whereIn('transaction_chain_id', $args['transactionIds']))
                        ->when(Arr::get($args, 'transactionHashes'), fn ($q) => $q->whereIn('transaction_chain_hash', $args['transactionIds']))
                        ->when(Arr::get($args, 'methods'), fn ($q) => $q->whereIn('method', $args['methods']))
                        ->when(Arr::get($args, 'states'), fn ($q) => $q->whereIn('state', $args['states']));

                    break;
                case 'ownedCollections':
                    $withCount[$relation] = fn ($query) => $query->when(
                        Arr::get($args, 'collectionIds'),
                        fn ($q) => $q->whereIn('collection_chain_id', $args['collectionIds'])
                    );

                    break;
                case 'tokenAccountApprovals':
                case 'collectionAccountApprovals':
                    $withCount[] = $relation;

                    break;
            }

            $with = array_merge(
                $with,
                static::getRelationQuery(
                    WalletType::class,
                    $relation,
                    $fields,
                    $key,
                    $with
                )
            );
        }

        return [$select, $with, $withCount];
    }

    /**
     * Load select and relationship fields.
     */
    public static function selectFields(ResolveInfo $resolveInfo, string $query): array
    {
        $select = ['*'];
        $with = [];
        $withCount = [];
        static::$query = $query;
        $queryPlan = $resolveInfo->lookAhead()->queryPlan();

        switch ($query) {
            case 'GetBlocks':
                $fields = Arr::get($queryPlan, 'edges.fields.node.fields', []);
                $select = BlockType::getSelectFields(array_keys($fields));
                // Number must always be selected as pagination is done based on that
                in_array('number', $select) || $select[] = 'number';

                break;
            case 'GetPendingEvents':
                $fields = Arr::get($queryPlan, 'edges.fields.node.fields', []);
                $select = array_unique([
                    'id',
                    ...PendingEventType::getSelectFields(array_keys($fields)),
                ]);

                break;
            case 'GetCollections':
            case 'GetCollection':
                [$select, $with, $withCount] = static::loadCollection(
                    $queryPlan,
                    $query == 'GetCollections' ? 'edges.fields.node.fields' : '',
                    [],
                    null,
                    true
                );

                break;
            case 'GetToken':
            case 'GetTokens':
                [$select, $with, $withCount] = static::loadToken(
                    $queryPlan,
                    $query == 'GetTokens' ? 'edges.fields.node.fields' : '',
                    [],
                    null,
                    true
                );

                break;
            case 'GetTransaction':
            case 'GetTransactions':
            case 'MarkAndListPendingTransactions':
                [$select, $with, $withCount] = static::loadTransaction(
                    $queryPlan,
                    in_array($query, ['GetTransactions', 'MarkAndListPendingTransactions']) ? 'edges.fields.node.fields' : '',
                    [],
                    null,
                    true
                );

                break;
            case 'GetWallet':
            case 'GetWallets':
                [$select, $with, $withCount] = static::loadWallet(
                    $queryPlan,
                    $query == 'GetWallets' ? 'edges.fields.node.fields' : '',
                    [],
                    null,
                    true
                );

                break;
        }


        return [$select, $with, $withCount];
    }

    /**
     * Eager load selects and relationships.
     */
    public static function loadSelectFields(ResolveInfo $resolveInfo, string $query): Builder
    {
        [$select, $with, $withCount] = static::selectFields($resolveInfo, $query);

        return static::query()->select($select)->with($with)->withCount($withCount);
    }

    /**
     * Get attribute alias.
     */
    public static function getAlias(string $name, ?string $type = null): string
    {
        return match (true) {
            $name == 'owner' || $name == 'royaltyBeneficiary' => 'wallet',
            $name == 'approvals' && $type == TokenAccountType::class => 'tokenAccountApprovals',
            $name == 'account' && $type == TokenAccountApprovalType::class => 'tokenAccount',
            $name == 'accounts' && $type == TokenType::class => 'tokenAccounts',
            $name == 'approvals' && $type == CollectionAccountType::class => 'collectionAccountApprovals',
            $name == 'account' && $type == CollectionAccountApprovalType::class => 'collectionAccount',
            $name == 'accounts' && $type == CollectionType::class => 'collectionAccounts',
            $name == 'ownedCollections' && $type == WalletType::class => 'collections',
            default => $name
        };
    }

    /**
     * Get relationship query.
     */
    public static function getRelationQuery(
        string $parentType,
        string $attribute,
        array $selections,
        ?string $parent = null,
        array $withs = []
    ): array {
        $key = $parent ? "{$parent}.{$attribute}" : $attribute;
        $alias = static::getAlias($attribute, $parentType);
        $args = Arr::get($selections, $attribute . '.args', []);
        switch ($alias) {
            case 'collection':
            case 'collections':
                $relations = static::loadCollection(
                    $selections,
                    $alias == 'collections' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    $args,
                    $key
                );
                $withs = array_merge($withs, $relations[1]);

                break;
            case 'token':
            case 'tokens':
                $relations = static::loadToken(
                    $selections,
                    $alias == 'tokens' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    $args,
                    $key
                );
                $withs = array_merge($withs, $relations[1]);

                break;
            case 'attributes':
                $withs = array_merge(
                    $withs,
                    [$key => fn ($query) => $query->select(['id', 'key', 'value', 'collection_id', 'token_id'])]
                );

                break;
            case 'events':
                $fields = Arr::get($selections, $attribute . '.fields.edges.fields.node.fields', []);
                $select = array_filter([
                    'id',
                    'transaction_id',
                    ...EventType::getSelectFields(array_keys($fields)),
                ]);
                $withs = array_merge($withs, [$key => fn ($query) => $query->select(array_unique($select))]);

                break;
            case 'wallet':
                $relations = static::loadWallet(
                    $selections,
                    $attribute == 'royaltyBeneficiary'
                        ? 'royalty.fields.beneficiary.fields'
                        : $attribute . '.fields',
                    $args,
                    $key
                );
                $withs = array_merge($withs, $relations[1]);

                break;
            case 'tokenAccountApproval':
            case 'tokenAccountApprovals':
                $fields = Arr::get(
                    $selections,
                    static::$query == 'GetWallet' && $attribute != 'approvals' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    []
                );
                $select = array_filter([
                    'id',
                    'token_account_id',
                    isset($fields['wallet']) || static::$query == 'GetWallet' ? 'wallet_id' : null,
                    ...TokenAccountApprovalType::getSelectFields($fieldKeys = array_keys($fields)),
                ]);
                $withs = array_merge($withs, [$key => fn ($query) => $query->select(array_unique($select))]);

                foreach (TokenAccountApprovalType::getRelationFields($fieldKeys) as $relation) {
                    $withs = array_merge(
                        $withs,
                        static::getRelationQuery(
                            TokenAccountApprovalType::class,
                            $relation,
                            $fields,
                            $key,
                            $withs
                        )
                    );
                }

                break;
            case 'tokenAccount':
            case 'tokenAccounts':
                $fields = Arr::get(
                    $selections,
                    $alias == 'tokenAccounts' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    []
                );
                $select = array_filter([
                    'id',
                    'token_id',
                    isset($fields['collection']) ? 'collection_id' : null,
                    isset($fields['wallet']) || static::$query == 'GetWallet' ? 'wallet_id' : null,
                    ...TokenAccountType::getSelectFields($fieldKeys = array_keys($fields)),
                ]);

                $withs = array_merge(
                    $withs,
                    [$key => function ($query) use ($select, $args): void {
                        $query->select(array_unique($select))
                            ->when($cursor = Cursor::fromEncoded(Arr::get($args, 'after')), fn ($q) => $q->where('id', '>', $cursor->parameter('id')))
                            ->when(
                                Arr::get($args, 'collectionIds'),
                                fn ($q) => $q->whereIn(
                                    'collection_id',
                                    DB::table('collections')->select('id')->whereIn('collection_chain_id', $args['collectionIds'])
                                )
                            )
                            ->when(
                                Arr::get($args, 'tokenIds'),
                                fn ($q) => $q->whereIn(
                                    'token_id',
                                    DB::table('tokens')->select('id')->whereIn('token_chain_id', $args['tokenIds'])
                                )
                            )->orderBy('token_accounts.id');
                        // This must be done this way to load eager limit correctly.
                        if ($limit = Arr::get($args, 'first')) {
                            $query->limit($limit + 1);
                        }
                    }]
                );

                foreach (TokenAccountType::getRelationFields($fieldKeys) as $relation) {
                    $withs = array_merge(
                        $withs,
                        static::getRelationQuery(
                            TokenAccountType::class,
                            $relation,
                            $fields,
                            $key,
                            $withs
                        )
                    );
                }

                break;
            case 'collectionAccount':
            case 'collectionAccounts':
                $fields = Arr::get(
                    $selections,
                    $alias == 'collectionAccounts' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    []
                );
                $select = array_filter([
                    'id',
                    'collection_id',
                    isset($fields['wallet']) || static::$query == 'GetWallet' ? 'wallet_id' : null,
                    ...CollectionAccountType::getSelectFields($fieldKeys = array_keys($fields)),
                ]);

                $withs = array_merge(
                    $withs,
                    [$key => function ($query) use ($select, $args): void {
                        $query->select(array_unique($select))
                            ->when($cursor = Cursor::fromEncoded(Arr::get($args, 'after')), fn ($q) => $q->where('id', '>', $cursor->parameter('id')))
                            ->when(
                                Arr::get($args, 'collectionIds'),
                                fn ($q) => $q->whereIn(
                                    'collection_id',
                                    DB::table('collections')->select('id')->whereIn('collection_chain_id', $args['collectionIds'])
                                )
                            )->orderBy('collection_accounts.id');
                        if ($limit = Arr::get($args, 'first')) {
                            $query->limit($limit + 1);
                        }
                    }]
                );

                foreach (CollectionAccountType::getRelationFields($fieldKeys) as $relation) {
                    $withs = array_merge(
                        $withs,
                        static::getRelationQuery(
                            CollectionAccountType::class,
                            $relation,
                            $fields,
                            $key,
                            $withs
                        )
                    );
                }

                break;
            case 'collectionAccountApproval':
            case 'collectionAccountApprovals':
                $fields = Arr::get(
                    $selections,
                    $alias == 'collectionAccountApprovals' && $attribute != 'approvals' ? $attribute . '.fields.edges.fields.node.fields' : $attribute . '.fields',
                    []
                );
                $select = array_filter([
                    'id',
                    'collection_account_id',
                    isset($fields['wallet']) || static::$query == 'GetWallet' ? 'wallet_id' : null,
                    ...CollectionAccountApprovalType::getSelectFields($fieldKeys = array_keys($fields)),
                ]);

                $withs = array_merge($withs, [$key => fn ($query) => $query->select($select)]);
                foreach (CollectionAccountApprovalType::getRelationFields($fieldKeys) as $relation) {
                    $withs = array_merge(
                        $withs,
                        static::getRelationQuery(
                            CollectionAccountApprovalType::class,
                            $relation,
                            $fields,
                            $key,
                            $withs
                        )
                    );
                }

                break;
            case 'namedReserves':
                $fields = Arr::get($selections, $attribute . '.fields', []);
                $select = array_filter([
                    'id',
                    'token_account_id',
                    ...TokenAccountNamedReserveType::getSelectFields(array_keys($fields)),
                ]);
                $withs = array_merge($withs, [$key => fn ($query) => $query->select($select)]);

                break;
            case 'transactions':
                $relations = static::loadTransaction(
                    $selections,
                    $attribute . '.fields.edges.fields.node.fields',
                    $args,
                    $key
                );
                $withs = array_merge($withs, $relations[1]);

                break;
        }

        return $withs;
    }
}
