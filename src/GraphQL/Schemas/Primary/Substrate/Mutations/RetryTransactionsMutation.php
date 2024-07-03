<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RetryTransactionsMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'RetryTransactions',
            'description' => __('enjin-platform::mutation.retry_transaction.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return [
            'ids' => [
                'type' => GraphQL::type('[BigInt!]'),
                'description' => __('enjin-platform::query.get_transaction.args.id'),
            ],
            'idempotencyKeys' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::query.get_transaction.args.idempotencyKey'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields
    ): mixed {
        $prepare = ($ids = Arr::get($args, 'ids'))
                ? Transaction::whereIn('id', $ids)
                : Transaction::whereIn('idempotency_key', Arr::get($args, 'idempotencyKeys'));

        return (bool) $prepare->update(['state' => TransactionState::PENDING->name, 'transaction_chain_hash' => null]);
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'ids' => [
                'required_without:idempotencyKeys',
                'prohibits:idempotencyKeys',
                'array',
                'min:1',
                'max:1000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!Transaction::whereIn('id', $value)
                        ->where('state', '!=', TransactionState::FINALIZED->name)
                        ->exists()) {
                        $fail('validation.exists')->translate();
                    }
                },
            ],
            'ids.*' => ['bail', 'distinct', new MinBigInt(), new MaxBigInt()],
            'idempotencyKeys' => [
                'required_without:ids',
                'prohibits:ids',
                'array',
                'min:1',
                'max:1000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!Transaction::whereIn('idempotency_key', $value)
                        ->where('state', '!=', TransactionState::FINALIZED->name)
                        ->exists()) {
                        $fail('validation.exists')->translate();
                    }
                },
            ],
            'idempotencyKeys.*' => ['bail', 'filled', 'max:255', 'distinct'],
        ];
    }
}
