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
use Enjin\Platform\Rules\IsManagedWallet;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class TransferBalanceMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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
            'name' => 'TransferBalance',
            'description' => __('enjin-platform::mutation.transfer_balance.description'),
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
            'recipient' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.recipient'),
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.amount'),
            ],
            'keepAlive' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.keepAlive'),
                'defaultValue' => false,
            ],
            'signingAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.batch_transfer.args.signingAccount'),
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
        Substrate $blockchainService,
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService,
        WalletService $walletService
    ): mixed {
        $targetWallet = $walletService->firstOrStore(['account' => $args['recipient']]);
        $signingWallet = $walletService->firstOrStore([
            'account' => Arr::get($args, 'signingAccount') ?: Account::daemonPublicKey(),
        ]);

        $method = $this->getMethodName() . ($args['keepAlive'] ? 'KeepAlive' : '');
        $encodedData = $serializationService->encode($method, [
            $targetWallet->public_key,
            $args['amount'],
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
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipient' => ['filled', new ValidSubstrateAccount()],
            'amount' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'signingAccount' => '' === Arr::get($args, 'signingAccount') ? ['filled'] : ['nullable', 'bail', new ValidSubstrateAccount(), new IsManagedWallet()],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'signingAccount' => '' === Arr::get($args, 'signingAccount') ? ['filled'] : ['nullable', 'bail', new ValidSubstrateAccount()],
        ];
    }
}
