<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Rules\ValidSubstrateTransactionId;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetTransactionsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
        SingleFilterOnly::class,
    ];

    /**
     * Get the query's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'GetTransactions',
            'description' => __('enjin-platform::query.get_transactions.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Transaction', 'TransactionConnection');
    }

    /**
     * Get the query's arguments definition.
     */
    public function args(): array
    {
        return self::resolveArgs();
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        if (!empty($ids = Arr::get($args, 'ids'))) {
            return Transaction::loadSelectFields($resolveInfo, 'GetTransactions')
                ->whereIn('id', $ids)
                ->cursorPaginateWithTotalDesc('id', $args['first']);
        }

        if (!empty($transactionIds = Arr::get($args, 'transactionIds'))) {
            return Transaction::loadSelectFields($resolveInfo, 'GetTransactions')
                ->whereIn('transaction_chain_id', $transactionIds)
                ->cursorPaginateWithTotalDesc('id', $args['first']);
        }

        return Transaction::loadSelectFields($resolveInfo, 'GetTransactions')
            ->when(!empty($args['accounts']), function (Builder $query) use ($args) {
                $publicKeys = array_map(fn ($wallet) => SS58Address::getPublicKey($wallet), $args['accounts']);

                return $query->whereIn('wallet_public_key', $publicKeys);
            })
            ->when(!empty($args['transactionHashes']), fn (Builder $query) => $query->whereIn('transaction_chain_hash', $args['transactionHashes']))
            ->when(!empty($args['methods']), fn (Builder $query) => $query->whereIn('method', $args['methods']))
            ->when(!empty($args['states']), fn (Builder $query) => $query->whereIn('state', $args['states']))
            ->when(!empty($args['results']), fn (Builder $query) => $query->whereIn('result', $args['results']))
            ->when(!empty($args['signedAtBlocks']), fn (Builder $query) => $query->whereIn('signed_at_block', $args['signedAtBlocks']))
            ->when(!empty($args['idempotencyKeys']), fn (Builder $query) => $query->whereIn('idempotency_key', $args['idempotencyKeys']))
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Generic function for arguments definition.
     */
    public static function resolveArgs(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[BigInt]'),
                'description' => __('enjin-platform::query.get_transactions.args.ids'),
                'singleFilter' => true,
            ],
            'transactionIds' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_transactions.args.transactionIds'),
                'singleFilter' => true,
            ],
            'transactionHashes' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_transactions.args.hashes'),
                'filter' => true,
            ],
            'methods' => [
                'type' => GraphQL::type('[TransactionMethod]'),
                'description' => __('enjin-platform::query.get_transactions.args.methods'),
                'filter' => true,
            ],
            'states' => [
                'type' => GraphQL::type('[TransactionState]'),
                'description' => __('enjin-platform::query.get_transactions.args.states'),
                'filter' => true,
            ],
            'results' => [
                'type' => GraphQL::type('[TransactionResult]'),
                'description' => __('enjin-platform::query.get_transactions.args.results'),
                'filter' => true,
            ],
            'accounts' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_transactions.args.accounts'),
                'filter' => true,
            ],
            'signedAtBlocks' => [
                'type' => GraphQL::type('[Int]'),
                'description' => __('enjin-platform::query.get_transactions.args.signedAtBlocks'),
                'filter' => true,
            ],
            'idempotencyKeys' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_transaction.args.idempotencyKey'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Get the validation rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'ids' => ['nullable', 'bail', 'max:100', 'distinct'],
            'ids.*' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT64)],
            'transactionIds' => ['nullable', 'bail', 'max:100', 'distinct', new ValidSubstrateTransactionId()],
            'transactionHashes' => ['nullable', 'bail', 'max:100', 'distinct', new ValidHex(32)],
            'methods' => ['nullable', 'bail', 'distinct'],
            'states' => ['nullable', 'bail', 'distinct'],
            'results' => ['nullable', 'bail', 'distinct'],
            'eventIds' => ['nullable', 'bail', 'distinct'],
            'eventTypes' => ['nullable', 'bail', 'distinct'],
            'accounts' => ['nullable', 'bail', 'distinct', new ValidSubstrateAccount()],
            'accounts.*' => ['sometimes', new ValidSubstrateAccount()],
            'signedAtBlocks' => ['nullable', 'bail', 'max:100', 'distinct'],
            'signedAtBlocks.*' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT64)],
            'idempotencyKeys' => ['nullable', 'bail', 'max:100', 'distinct'],
            'idempotencyKeys.*' => ['sometimes', 'min:36', 'max:255'],
        ];
    }
}
