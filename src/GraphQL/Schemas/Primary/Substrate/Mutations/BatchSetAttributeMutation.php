<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
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
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchSetAttributeMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldRules;
    use HasTokenIdFields;
    use HasTransactionDeposit;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
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
    #[\Override]
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
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.attributes'),
            ],
            'continueOnFailure' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.common.args.continueOnFailure'),
                'defaultValue' => false,
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
        TransactionService $transactionService
    ): mixed {
        $continueOnFailure = $args['continueOnFailure'];
        $encodedData = $serializationService->encode($continueOnFailure ? 'Batch' : $this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            tokenId: $this->encodeTokenId($args),
            attributes: $args['attributes'],
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
        $tokenId = Arr::get($params, 'tokenId');
        $attributes = Arr::get($params, 'attributes', []);

        if ($continueOnFailure) {
            $encodedData = collect($attributes)->map(
                fn ($attribute) => $serializationService->encode('SetAttribute', [
                    'collectionId' => gmp_init($collectionId),
                    'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
                    'key' => HexConverter::stringToHexPrefixed($attribute['key']),
                    'value' => HexConverter::stringToHexPrefixed($attribute['value']),
                    'depositor' => null, // This is an internal input used by the blockchain internally
                ])
            );

            return [
                'calls' => $encodedData->toArray(),
                'continueOnFailure' => true,
            ];
        }

        return [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'depositor' => null, // This is an internal input used by the blockchain internally
            'attributes' => collect($attributes)
                ->map(fn ($attribute) => [
                    'key' => HexConverter::stringToHexPrefixed($attribute['key']),
                    'value' => HexConverter::stringToHexPrefixed($attribute['value']),
                ])->toArray(),
        ];
    }

    /**
     * Get the common rules.
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
            'collectionId' => ['bail', new IsCollectionOwner()],
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
