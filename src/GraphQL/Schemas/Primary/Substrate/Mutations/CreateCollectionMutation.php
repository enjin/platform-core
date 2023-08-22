<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Codec\Utils;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldArrayRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\DistinctAttributes;
use Enjin\Platform\Rules\DistinctMultiAsset;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Traits\InheritsGraphQlFields;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateCollectionMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InheritsGraphQlFields;
    use InPrimarySubstrateSchema;
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasTokenIdFieldArrayRules;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'CreateCollection',
            'description' => __('enjin-platform::mutation.create_collection.description'),
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
            'mintPolicy' => [
                'type' => GraphQL::type('MintPolicy!'),
                'description' => __('enjin-platform::mutation.create_collection.args.mintPolicy'),
            ],
            'marketPolicy' => [
                'type' => GraphQL::type('MarketPolicy'),
                'description' => __('enjin-platform::mutation.create_collection.args.marketPolicy'),
                'defaultValue' => null,
            ],
            'explicitRoyaltyCurrencies' => [
                'type' => GraphQL::type('[MultiTokenIdInput]'),
                'description' => __('enjin-platform::mutation.create_collection.args.explicitRoyaltyCurrencies'),
                'defaultValue' => [],
            ],
            'attributes' => [
                'type' => GraphQL::type('[AttributeInput]'),
                'description' => __('enjin-platform::mutation.create_collection.args.attributes'),
                'defaultValue' => [],
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
                'encoded_data' => $serializationService->encode($this->getMethodName(), $blockchainService->getCollectionPolicies($args)),
                'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                'deposit' => $this->getDepositValue($args),
                'simulate' => $args['simulate'],
            ]),
            $resolveInfo
        );
    }

    protected function getDepositValue(array $args): ?string
    {
        $collectionCreation = gmp_init('25000000000000000000');
        $depositBase = gmp_init('200000000000000000');
        $depositPerByte = gmp_init('100000000000000');
        $totalBytes = collect($args['attributes'])->sum(
            fn ($attribute) => count(Utils::string2ByteArray($attribute['key'] . $attribute['value']))
        );
        $attributes = $totalBytes > 0 ? gmp_add($depositBase, gmp_mul($depositPerByte, $totalBytes)) : gmp_init(0);

        return gmp_strval(gmp_add($collectionCreation, $attributes));
    }

    /**
     * Get common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'explicitRoyaltyCurrencies' => ['nullable', 'bail', 'array', 'min:0', 'max:10', new DistinctMultiAsset()],
            'attributes' => ['nullable', 'bail', 'array', 'min:0', 'max:10', new DistinctAttributes()],
            ...$this->getTokenFieldRules('explicitRoyaltyCurrencies.*', $args),
        ];
    }
}
