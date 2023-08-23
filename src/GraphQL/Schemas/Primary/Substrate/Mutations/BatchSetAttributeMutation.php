<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchSetAttributeMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasTokenIdFields;
    use HasTokenIdFieldRules;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'BatchSetAttribute',
            'description' => __('enjin-platform::mutation.batch_set_attribute.description'),
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
            ...$this->getTokenFields(__('enjin-platform::args.common.tokenId'), isOptional: true),
            'attributes' => [
                'type' => GraphQL::type('[AttributeInput!]!'),
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
        TransactionService $transactionService
    ): mixed {
        return Transaction::lazyLoadSelectFields(
            $transactionService->store([
                'method' => $this->getMutationName(),
                'encoded_data' => $this->resolveBatch($args['collectionId'], $this->encodeTokenId($args), $args['attributes'], false, $serializationService),
                'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                'deposit' => $this->getDeposit($args),
                'simulate' => $args['simulate'],
            ]),
            $resolveInfo
        );
    }

    /**
     * Resolve batch set attribute.
     */
    protected function resolveBatch(string $collectionId, ?string $tokenId, array $attributes, bool $continueOnFailure, SerializationServiceInterface $serializationService): string
    {
        if ($continueOnFailure) {
            return $this->resolveWithContinueOnFailure($collectionId, $tokenId, $attributes, $serializationService);
        }

        return $this->resolveWithoutContinueOnFailure($collectionId, $tokenId, $attributes, $serializationService);
    }

    /**
     * Resolve batch set attribute without continue on failure.
     */
    protected function resolveWithoutContinueOnFailure(string $collectionId, ?string $tokenId, array $attributes, SerializationServiceInterface $serializationService): string
    {
        return $serializationService->encode($this->getMethodName(), [
            'collectionId' => $collectionId,
            'tokenId' => $tokenId,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Resolve batch set attribute with continue on failure.
     */
    protected function resolveWithContinueOnFailure(string $collectionId, ?string $tokenId, array $attributes, SerializationServiceInterface $serializationService): string
    {
        $encodedData = collect($attributes)->map(
            fn ($attribute) => $serializationService->encode('setAttribute', [
                'collectionId' => $collectionId,
                'tokenId' => $tokenId,
                'key' => $attribute['key'],
                'value' => $attribute['value'],
            ])
        );

        return $serializationService->encode('batch', [
            'calls' => $encodedData->toArray(),
            'continueOnFailure' => true,
        ]);
    }

    /**
     * Get the commond rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'attributes' => ['array', 'min:1', 'max:20'],
            'attributes.*.key' => ['max:32'],
            'attributes.*.value' => ['max:255'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['bail', 'exists:collections,collection_chain_id', new IsCollectionOwner()],
            ...$this->getOptionalTokenFieldRulesExist(),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getOptionalTokenFieldRules();
    }
}
