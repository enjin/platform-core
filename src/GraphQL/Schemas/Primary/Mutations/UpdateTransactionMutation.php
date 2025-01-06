<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Rules\ImmutableTransaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Rules\ValidSubstrateTransactionId;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
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
    #[\Override]
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
    #[\Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int!'),
            ],
            'state' => [
                'type' => GraphQL::type('TransactionState'),
                'description' => __('enjin-platform::mutation.update_transaction.args.state'),
                'rules' => ['required_without_all:transactionId,transactionHash,signedAtBlock,signingAccount'],
            ],
            'transactionId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.update_transaction.args.transactionId'),
                'alias' => 'transaction_chain_id',
                'rules' => [
                    'nullable',
                    'required_without_all:state,transactionHash,signedAtBlock,signingAccount',
                    new ValidSubstrateTransactionId(),
                    new ImmutableTransaction(),
                ],
            ],
            'transactionHash' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.update_transaction.args.transactionHash'),
                'alias' => 'transaction_chain_hash',
                'rules' => [
                    'nullable',
                    'required_without_all:state,transactionId,signedAtBlock,signingAccount',
                    new ValidHex(32),
                    new ImmutableTransaction('transaction_chain_hash'),
                ],
            ],
            'signingAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
                'rules' => [
                    'nullable',
                    'required_without_all:state,transactionId,transactionHash,signedAtBlock',
                    new ValidSubstrateAccount(),
                ],
            ],
            'signedAtBlock' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.update_transaction.args.signedAtBlock'),
                'alias' => 'signed_at_block',
                'rules' => ['nullable', 'required_without_all:state,transactionId,transactionHash,signingAccount', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT64)],
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, TransactionService $transactionService): mixed
    {
        if (isset($args['signingAccount'])) {
            $args['wallet_public_key'] = SS58Address::getPublicKey($args['signingAccount']);
            unset($args['signingAccount']);
        }

        $transaction = $transactionService->get($args['id']);
        if (Arr::get($args, 'state') === TransactionState::PENDING->name &&
            Arr::has($args, 'transaction_chain_hash') &&
            is_null(Arr::get($args, 'transaction_chain_hash')) &&
            $transaction->state === TransactionState::FINALIZED->name
        ) {
            throw new PlatformException(__('enjin-platform::error.cannot_retry_transaction'));
        }

        return $transactionService->update($transaction, Arr::except($args, 'id'));
    }
}
