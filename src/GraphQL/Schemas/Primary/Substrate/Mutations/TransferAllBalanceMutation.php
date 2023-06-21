<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\IsManagedWallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class TransferAllBalanceMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasSkippableRules;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'TransferAllBalance',
            'description' => __('enjin-platform::mutation.transfer_all_balance.description'),
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
            'account' => Arr::get($args, 'signingAccount') ?: config('enjin-platform.chains.daemon-account'),
        ]);

        $encodedData = $serializationService->encode($this->getMethodName(), [
            $targetWallet->public_key,
            $args['keepAlive'],
        ]);

        return Transaction::lazyLoadSelectFields(
            $transactionService->store(
                [
                    'method' => $this->getMutationName(),
                    'encoded_data' => $encodedData,
                    'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
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
