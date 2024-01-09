<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Facades\Enjin\Platform\Services\Database\WalletService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class SendTransactionMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'SendTransaction',
            'description' => __('enjin-platform::mutation.update_transaction.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('String!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int'),
                'defaultValue' => null,
            ],
            'signingPayloadJson' => [
                'type' => GraphQL::type('Object'),
                'description' => __('enjin-platform::mutation.send_transaction.args.signing_payload_json'),
            ],
            'signature' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.send_transaction.args.signature'),
                'rules' => [
                    new ValidHex(),
                ],
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, TransactionService $transactionService, Substrate $substrate): mixed
    {
        $payload = Arr::get($args, 'signingPayloadJson');

        if (!Arr::has((array) $payload, ['address', 'method', 'nonce', 'era', 'tip'])) {
            throw new PlatformException(__('enjin-platform::error.signing_payload_json_is_invalid'));
        }

        $extrinsic = $substrate->createExtrinsic(
            $payload->address,
            Arr::get($args, 'signature'),
            $payload->method,
            $payload->nonce,
            $payload->era,
            $payload->tip
        );

        $transaction = null;

        if ($txId = $args['id']) {
            $transaction = Transaction::firstWhere(['id' => $txId]);

            if (!$transaction) {
                throw new PlatformException(__('enjin-platform::error.transaction_not_found'), 404);
            }
        }

        $response = $substrate->callMethod('author_submitExtrinsic', [$extrinsic], true);

        if (Arr::exists($response, 'error')) {
            throw new PlatformException($response['error']['message']);
        }

        $wallet = WalletService::firstOrStore([
            'account' => $payload->address,
        ]);

        if (!$transaction) {
            $transactionService->store(
                [
                    'method' => 'SendTransaction',
                    'encoded_data' => $payload->method,
                    'idempotency_key' => Str::uuid()->toString(),
                    'transaction_chain_hash' => $response['result'],
                    'state' => TransactionState::BROADCAST->name,
                ],
                signingWallet: $wallet,
            );
        } else {
            $transactionService->update(
                $transaction,
                [
                    'wallet_public_key' => $wallet->public_key,
                    'transaction_chain_hash' => $response['result'],
                    'state' => TransactionState::BROADCAST->name,
                ],
            );
        }

        return $response['result'];
    }
}
