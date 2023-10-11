<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class SimpleTransferTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFieldRules;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;
    use HasSigningAccountField;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'SimpleTransferToken',
            'description' => __('enjin-platform::mutation.simple_transfer_token.description'),
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
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.collectionId'),
            ],
            'recipient' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.recipient'),
            ],
            'params' => [
                'type' => GraphQL::type('SimpleTransferParams!'),
            ],
            ...$this->getSigningAccountField(),
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
        Substrate $blockchainService,
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService,
        WalletService $walletService
    ): mixed {
        $targetWallet = $walletService->firstOrStore(['account' => $args['recipient']]);
        $signingWallet = $walletService->firstOrStore([
            'account' => $this->getSigningAccount($args),
        ]);

        $encodedData = $serializationService->encode($this->getMethodName(), [
            $targetWallet->public_key,
            $args['collectionId'],
            $blockchainService->getSimpleTransferParams($args['params']),
        ]);

        return Transaction::lazyLoadSelectFields(
            $transactionService->store(
                [
                    'method' => $this->getMutationName(),
                    'encoded_data' => $encodedData,
                    'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                    'deposit' => $this->getDeposit($args),
                    'simulate' => $args['simulate'],
                ],
                signingWallet: $signingWallet
            ),
            $resolveInfo
        );
    }

    /**
     * Get the serialization service method name.
     */
    public function getMethodName(): string
    {
        return 'transferToken';
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipient' => ['filled', new ValidSubstrateAccount()],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['exists:collections,collection_chain_id'],
            ...$this->getTokenFieldRulesExist('params'),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getTokenFieldRules('params');
    }
}
