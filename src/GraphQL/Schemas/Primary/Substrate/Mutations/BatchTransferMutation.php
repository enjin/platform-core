<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldArrayRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\IsManagedWallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchTransferMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFieldArrayRules;
    use HasEncodableTokenId;
    use HasSkippableRules;
    use HasSimulateField;

    /**
     * Get the mutation's attributes.
     */
    #[ArrayShape(['name' => 'string', 'description' => 'string'])]
    public function attributes(): array
    {
        return [
            'name' => 'BatchTransfer',
            'description' => __('enjin-platform::mutation.batch_transfer.description'),
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
                'description' => __('enjin-platform::mutation.batch_transfer.args.collectionId'),
            ],
            'recipients' => [
                'type' => GraphQL::type('[TransferRecipient!]!'),
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
        $signingWallet = $walletService->firstOrStore([
            'account' => $args['signingAccount'] ?? Account::daemonPublicKey(),
        ]);

        $recipients = collect($args['recipients'])->map(
            function ($recipient) use ($blockchainService, $walletService) {
                $simpleParams = Arr::get($recipient, 'simpleParams');
                $operatorParams = Arr::get($recipient, 'operatorParams');

                if (null !== $simpleParams && null !== $operatorParams) {
                    throw new PlatformException(__('enjin-platform::error.cannot_set_simple_and_operator_params_for_same_recipient'));
                }
                if (null === $simpleParams && null === $operatorParams) {
                    throw new PlatformException(__('enjin-platform::error.set_either_simple_and_operator_params_for_recipient'));
                }

                $targetWallet = $walletService->firstOrStore(['account' => $recipient['account']]);

                return [
                    'accountId' => $targetWallet->public_key,
                    'params' => $blockchainService->getTransferParams($simpleParams ?? $operatorParams),
                ];
            }
        );

        return Transaction::lazyLoadSelectFields(
            $transactionService->store(
                [
                    'method' => $this->getMutationName(),
                    'encoded_data' => $this->resolveBatch($args['collectionId'], $recipients, false, $serializationService),
                    'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                    'simulate' => $args['simulate'] ?? false,
                ],
                signingWallet: $signingWallet
            ),
            $resolveInfo
        );
    }

    /**
     * Resolve batch transfer.
     */
    protected function resolveBatch(string $collectionId, Collection $recipients, bool $continueOnFailure, SerializationServiceInterface $serializationService): string
    {
        if ($continueOnFailure) {
            return $this->resolveWithContinueOnFailure($collectionId, $recipients, $serializationService);
        }

        return $this->resolveWithoutContinueOnFailure($collectionId, $recipients, $serializationService);
    }

    /**
     * Resolve batch transfer without continue on failure.
     */
    protected function resolveWithoutContinueOnFailure(string $collectionId, Collection $recipients, SerializationServiceInterface $serializationService): string
    {
        return $serializationService->encode($this->getMethodName(), [
            'collectionId' => $collectionId,
            'recipients' => $recipients->toArray(),
        ]);
    }

    /**
     * Resolve batch transfer with continue on failure.
     */
    protected function resolveWithContinueOnFailure(string $collectionId, Collection $recipients, SerializationServiceInterface $serializationService): string
    {
        $encodedData = $recipients->map(
            fn ($recipient) => $serializationService->encode('transferToken', [
                'recipient' => $recipient['accountId'],
                'collectionId' => $collectionId,
                'params' => $recipient['params'],
            ])
        );

        return $serializationService->encode('batch', [
            'calls' => $encodedData->toArray(),
            'continueOnFailure' => true,
        ]);
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipients' => ['array', 'min:1', 'max:250'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['exists:collections,collection_chain_id'],
            'signingAccount' => ['nullable', 'bail', new ValidSubstrateAccount(), new IsManagedWallet()],
            ...$this->getTokenFieldRulesExist('recipients.*.simpleParams', $args),
            ...$this->getTokenFieldRulesExist('recipients.*.operatorParams', $args),
        ];
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'signingAccount' => ['nullable', 'bail', new ValidSubstrateAccount()],
            ...$this->getTokenFieldRules('recipients.*.simpleParams', $args),
            ...$this->getTokenFieldRules('recipients.*.operatorParams', $args),
        ];
    }
}
