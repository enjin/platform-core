<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
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
        $extrinsic = $substrate->createExtrinsic(
            $payload->address,
            Arr::get($args, 'signature'),
            $payload->method,
            $payload->nonce,
            $payload->era,
            $payload->tip
        );

        $response = $substrate->callMethod('author_submitExtrinsic', [$extrinsic], true);

        if (Arr::exists($response, 'error')) {
            throw new PlatformException($response['error']['message']);
        }

        $transactionService->update($transactionService->get($args['id']), [
            'transaction_chain_hash' => $hash = $response['result'],
            'state' => TransactionState::BROADCAST->name,
        ]);

        return $hash;
    }
}
