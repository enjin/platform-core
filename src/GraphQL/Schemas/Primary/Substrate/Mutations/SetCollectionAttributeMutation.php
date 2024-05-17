<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\StringMaxByteLength;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class SetCollectionAttributeMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTransactionDeposit;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'SetCollectionAttribute',
            'description' => __('enjin-platform::mutation.set_collection_attribute.description'),
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
            'key' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.key'),
            ],
            'value' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.value'),
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
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService
    ): mixed {
        $encodedData = $serializationService->encode($this->getMethodName(), static::getEncodableParams(...$args));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    /**
     * Get the serialization service method name.
     */
    public function getMethodName(): string
    {
        return 'SetAttribute';
    }

    public static function getEncodableParams(...$params): array
    {
        $collectionId = Arr::get($params, 'collectionId', 0);
        $key = Arr::get($params, 'key', '0');
        $value = Arr::get($params, 'value', '0');

        return [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => null,
            'key' => HexConverter::stringToHexPrefixed($key),
            'value' => HexConverter::stringToHexPrefixed($value),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'key' => ['filled', 'alpha_dash', new StringMaxByteLength(256)],
            'value' => ['filled', new StringMaxByteLength(1024)],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new IsCollectionOwner()],
        ];
    }
}
