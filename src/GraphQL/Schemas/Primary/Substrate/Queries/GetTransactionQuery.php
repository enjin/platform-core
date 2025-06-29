<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\SingleArgOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateTransactionId;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetTransactionQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        SingleArgOnly::class,
    ];

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetTransaction',
            'description' => __('enjin-platform::query.get_transaction.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Transaction');
    }

    /**
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::query.get_transaction.args.id'),
                'rules' => ['bail', 'filled', new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT64)],
            ],
            'transactionId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_transaction.args.transactionId'),
                'deprecationReason' => '',
                'rules' => ['bail', 'filled', new ValidSubstrateTransactionId()],
            ],
            'transactionHash' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_transaction.args.transactionHash'),
                'rules' => ['bail', 'filled', new ValidHex(32)],
            ],
            //            'idempotencyKey' => [
            //                'type' => GraphQL::type('String'),
            //                'description' => __('enjin-platform::query.get_transaction.args.idempotencyKey'),
            //                'rules' => ['bail', 'filled', 'min:36', 'max:255'],
            //            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return  Transaction::selectFields($getSelectFields)
            ->when(Arr::get($args, 'id'), fn (Builder $query) => $query->where('id', $args['id']))
            ->when(Arr::get($args, 'transactionId'), fn (Builder $query) => $query->where('transaction_chain_id', $args['transactionId']))
            ->when(Arr::get($args, 'transactionHash'), fn (Builder $query) => $query->where('transaction_chain_hash', $args['transactionHash']))
//            ->when(Arr::get($args, 'idempotencyKey'), fn (Builder $query) => $query->where('idempotency_key', $args['idempotencyKey']))
            ->first();
    }
}
