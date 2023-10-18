<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldArrayRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\CheckTokenCount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchMintMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFieldArrayRules;
    use HasSkippableRules;
    use HasEncodableTokenId;
    use HasSimulateField;
    use HasTransactionDeposit;
    use HasSigningAccountField;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'BatchMint',
            'description' => __('enjin-platform::mutation.batch_mint.description'),
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
                'description' => __('enjin-platform::mutation.batch_mint.args.collectionId'),
            ],
            'recipients' => [
                'type' => GraphQL::type('[MintRecipient!]!'),
                'rules' => ['array', 'min:1', 'max:250'],
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
        $recipients = collect($args['recipients'])->map(
            function ($recipient) use ($blockchainService, $walletService) {
                $createParams = Arr::get($recipient, 'createParams');
                $mintParams = Arr::get($recipient, 'mintParams');

                if (Arr::get($createParams, 'cap.type') === TokenMintCapType::SUPPLY->name) {
                    if (null === Arr::get($createParams, 'cap.amount')) {
                        throw new PlatformException(__('enjin-platform::error.supply_cap_must_be_set'));
                    }
                    if (Arr::get($createParams, 'cap.amount') < Arr::get($createParams, 'initialSupply')) {
                        throw new PlatformException(__('enjin-platform::error.supply_cap_must_be_greater_than_initial'));
                    }
                }
                if (null !== $createParams && null !== $mintParams) {
                    throw new PlatformException(__('enjin-platform::error.cannot_set_create_and_mint_params_with_same_recipient'));
                }
                if (null === $createParams && null === $mintParams) {
                    throw new PlatformException(__('enjin-platform::error.set_either_create_or_mint_param_for_recipient'));
                }

                $recipientWallet = $walletService->firstOrStore(['account' => $recipient['account']]);

                return [
                    'accountId' => $recipientWallet->public_key,
                    'params' => $blockchainService->getMintOrCreateParams($createParams ?? $mintParams),
                ];
            }
        );

        return Transaction::lazyLoadSelectFields(
            $transactionService->store(
                [
                    'method' => $this->getMutationName(),
                    'encoded_data' => $this->resolveBatch($args['collectionId'], $recipients, false, $serializationService),
                    'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                    'deposit' => $this->getDeposit($args),
                    'simulate' => $args['simulate'],
                ],
                signingWallet: $this->getSigningAccount($args),
            ),
            $resolveInfo
        );
    }

    /**
     * Resolve batch mint.
     */
    protected function resolveBatch(string $collectionId, Collection $recipients, bool $continueOnFailure, SerializationServiceInterface $serializationService): string
    {
        if ($continueOnFailure) {
            return $this->resolveWithContinueOnFailure($collectionId, $recipients, $serializationService);
        }

        return $this->resolveWithoutContinueOnFailure($collectionId, $recipients, $serializationService);
    }

    /**
     * Resolve batch mint without continue on failure.
     */
    protected function resolveWithoutContinueOnFailure(string $collectionId, Collection $recipients, SerializationServiceInterface $serializationService): string
    {
        return $serializationService->encode($this->getMethodName(), [
            'collectionId' => $collectionId,
            'recipients' => $recipients->toArray(),
        ]);
    }

    /**
     * Resolve batch mint with continue on failure.
     */
    protected function resolveWithContinueOnFailure(string $collectionId, Collection $recipients, SerializationServiceInterface $serializationService): string
    {
        $encodedData = $recipients->map(
            fn ($recipient) => $serializationService->encode('mint', [
                'collectionId' => $collectionId,
                'recipientId' => $recipient['accountId'],
                'params' => $recipient['params'],
            ])
        );

        return $serializationService->encode('batch', [
            'calls' => $encodedData->toArray(),
            'continueOnFailure' => true,
        ]);
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [
                'exists:collections,collection_chain_id',
                new CheckTokenCount(collect($args['recipients'])->pluck('createParams')->filter()->count()),
            ],
            ...$this->getTokenFieldRulesDoesntExist('recipients.*.createParams', $args),
            ...$this->getTokenFieldRulesExist('recipients.*.mintParams', $args),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            ...$this->getTokenFieldRules('recipients.*.createParams', $args),
            ...$this->getTokenFieldRules('recipients.*.mintParams', $args),
        ];
    }
}
