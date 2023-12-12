<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
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
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
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
    use StoresTransactions;

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

        // Hard-coded to false until we add support for this feature back into the mutations.
        $continueOnFailure = false;
        $encodedData = $serializationService->encode($continueOnFailure ? 'Batch' : $this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            recipients: $recipients->toArray(),
            continueOnFailure: $continueOnFailure
        ));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    public static function getEncodableParams(...$params): array
    {
        $serializationService = resolve(SerializationServiceInterface::class);
        $continueOnFailure = Arr::get($params, 'continueOnFailure', false);
        $collectionId = Arr::get($params, 'collectionId', 0);
        $recipients = Arr::get($params, 'recipients', []);

        if ($continueOnFailure) {
            $encodedData = $recipients->map(
                fn ($recipient) => $serializationService->encode('Mint', [
                    'collectionId' => gmp_init($collectionId),
                    'recipient' => [
                        'Id' => HexConverter::unPrefix($recipient['accountId']),
                    ],
                    'params' => $recipient['params']->toEncodable(),
                ])
            );

            return [
                'calls' => $encodedData->toArray(),
                'continueOnFailure' => true,
            ];
        }

        return [
            'collectionId' => gmp_init($collectionId),
            'recipients' => collect($recipients)->map(fn ($recipient) => [
                'accountId' => HexConverter::unPrefix($recipient['accountId']),
                'params' => $recipient['params']->toEncodable(),
            ])->toArray(),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [
                new IsCollectionOwner(),
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
