<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\CollectionHasTokens;
use Enjin\Platform\Rules\DaemonProhibited;
use Enjin\Platform\Rules\FutureBlock;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ApproveCollectionMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'ApproveCollection',
            'description' => __('enjin-platform::mutation.approve_collection.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Transaction!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.approve_collection.args.collectionId'),
            ],
            'operator' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.approve_collection.args.operator'),
            ],
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.approve_token.args.expiration'),
                'defaultValue' => null,
            ],
            ...$this->getIdempotencyField(),
            ...$this->getSkipValidationField(),
            ...$this->getSimulateField(),
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
        Closure $getSelectFields,
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService,
        WalletService $walletService
    ): mixed {
        $operatorWallet = $walletService->firstOrStore(['account' => $args['operator']]);
        $encodedData = $serializationService->encode($this->getMethodName(), [
            'collectionId' => $args['collectionId'],
            'operator' => $operatorWallet->public_key,
            'expiration' => $args['expiration'],
        ]);

        return Transaction::lazyLoadSelectFields(
            $transactionService->store([
                'method' => $this->getMutationName(),
                'encoded_data' => $encodedData,
                'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                'deposit' => $this->getDeposit($args),
                'simulate' => $args['simulate'],
            ]),
            $resolveInfo
        );
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new CollectionHasTokens()],
            'operator' => ['filled', new ValidSubstrateAccount(), new DaemonProhibited()],
            'expiration' => ['nullable', 'integer', new FutureBlock()],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'operator' => ['filled', new ValidSubstrateAccount()],
            'expiration' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
