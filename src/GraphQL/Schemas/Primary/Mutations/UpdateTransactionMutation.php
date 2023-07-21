<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Rules\ImmuteableTransaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateTransactionId;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateTransactionMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'UpdateTransaction',
            'description' => __('enjin-platform::mutation.update_transaction.description'),
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
            'id' => [
                'type' => GraphQL::type('Int!'),
            ],
            'state' => [
                'type' => GraphQL::type('TransactionState'),
                'description' => __('enjin-platform::mutation.update_transaction.args.state'),
                'rules' => ['required_without_all:transactionId,transactionHash,signedAtBlock'],
            ],
            'transactionId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.update_transaction.args.transactionId'),
                'alias' => 'transaction_chain_id',
                'rules' => [
                    'nullable',
                    'required_without_all:state,transactionHash,signedAtBlock',
                    new ValidSubstrateTransactionId(),
                    new ImmuteableTransaction(),
                ],
            ],
            'transactionHash' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.update_transaction.args.transactionHash'),
                'alias' => 'transaction_chain_hash',
                'rules' => [
                    'nullable',
                    'required_without_all:state,transactionId,signedAtBlock',
                    new ValidHex(32),
                    new ImmuteableTransaction('transaction_chain_hash'),
                ],
            ],
            'signedAtBlock' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.update_transaction.args.signedAtBlock'),
                'alias' => 'signed_at_block',
                'rules' => ['nullable', 'required_without_all:state,transactionId,transactionHash', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT64)],
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, TransactionService $transactionService): mixed
    {
        return $transactionService->update($transactionService->get($args['id']), Arr::except($args, 'id'));
    }
}
