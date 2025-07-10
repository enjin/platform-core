<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
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
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Rules\DistinctAttributes;
use Enjin\Platform\Rules\DistinctMultiAsset;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Traits\InheritsGraphQlFields;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateCollectionMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldArrayRules;
    use HasTransactionDeposit;
    use InheritsGraphQlFields;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[Override]
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
    #[Override]
    public function args(): array
    {
        return [
            'mintPolicy' => [
                'type' => GraphQL::type('MintPolicy'),
                'description' => __('enjin-platform::mutation.create_collection.args.mintPolicy'),
                'defaultValue' => null,
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
    ): mixed {
        return $this->storeTransaction(
            $args,
            $serializationService->encode(
                $this->getMutationName() . (currentSpec() >= 1020 ? '' : 'V1013'),
                static::getEncodableParams(...$blockchainService->getCollectionPolicies($args))
            )
        );
    }

    public static function getEncodableParams(...$params): array
    {
        $mintPolicy = Arr::get($params, 'mintPolicy') ?? new MintPolicyParams();
        $marketPolicy = Arr::get($params, 'marketPolicy');
        $explicitRoyaltyCurrencies = Arr::get($params, 'explicitRoyaltyCurrencies', []);
        $attributes = Arr::get($params, 'attributes', []);

        return [
            'descriptor' => [
                'policy' => [
                    'mint' => $mintPolicy->toEncodable(),
                    'market' => $marketPolicy?->toEncodable(),
                ],
                'explicitRoyaltyCurrencies' => array_map(
                    fn ($multiToken) => [
                        'collectionId' => gmp_init($multiToken['collectionId']),
                        'tokenId' => gmp_init($multiToken['tokenId']),
                    ],
                    $explicitRoyaltyCurrencies
                ),
                'attributes' => array_map(
                    fn ($attribute) => [
                        'key' => HexConverter::stringToHexPrefixed($attribute['key']),
                        'value' => HexConverter::stringToHexPrefixed($attribute['value']),
                    ],
                    $attributes
                ),
                'depositor' => null,
            ],
        ];
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
