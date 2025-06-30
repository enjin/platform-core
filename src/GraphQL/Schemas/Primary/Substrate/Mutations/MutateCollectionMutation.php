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
use Enjin\Platform\Rules\DistinctMultiAsset;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\ValidRoyaltyPercentage;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MutateCollectionMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldArrayRules;
    use HasTransactionDeposit;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'MutateCollection',
            'description' => __('enjin-platform::mutation.mutate_collection.description'),
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
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.mutate_collection.args.collectionId'),
            ],
            'mutation' => [
                'type' => GraphQL::type('CollectionMutationInput!'),
                'description' => __('enjin-platform::mutation.mutate_collection.args.mutation'),
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
        WalletService $walletService,
        Substrate $blockchainService
    ): mixed {
        if ($currency = Arr::get($args, 'mutation.explicitRoyaltyCurrencies')) {
            Arr::set(
                $args,
                'mutation.explicitRoyaltyCurrencies',
                collect($currency)
                    ->map(function ($row) {
                        $row['tokenId'] = $this->encodeTokenId($row);
                        unset($row['encodeTokenId']);

                        return $row;
                    })->toArray()
            );
        }

        $encodedData = $serializationService->encode(
            $this->getMutationName() . (currentSpec() >= 1020 ? '' : 'V1013'),
            static::getEncodableParams(
                collectionId: $args['collectionId'],
                owner: ($owner = Arr::get($args, 'mutation.owner')) !== null
                ? SS58Address::getPublicKey($owner)
                : null,
                royalty: $blockchainService->getMutateCollectionRoyalty(Arr::get($args, 'mutation')),
                explicitRoyaltyCurrencies: Arr::get($args, 'mutation.explicitRoyaltyCurrencies'),
            )
        );

        return $this->storeTransaction($args, $encodedData);
    }

    public static function getEncodableParams(...$params): array
    {
        $owner = Arr::get($params, 'owner');
        $royalty = Arr::get($params, 'royalty');
        $explicitRoyaltyCurrencies = Arr::get($params, 'explicitRoyaltyCurrencies');

        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'mutation' => [
                'owner' => $owner !== null ? HexConverter::unPrefix($owner) : null,
                'royalty' => is_array($royalty) ? ['NoMutation' => null] : ['SomeMutation' => $royalty?->toEncodable()],
                'explicitRoyaltyCurrencies' => $explicitRoyaltyCurrencies !== null ? collect($explicitRoyaltyCurrencies)
                    ->map(fn ($multiToken) => [
                        'collectionId' => gmp_init($multiToken['collectionId']),
                        'tokenId' => gmp_init($multiToken['tokenId']),
                    ])->toArray()
                    : null,
            ],
        ];
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        $isOwnerEmpty = Arr::get($args, 'mutation.owner') === '';
        $isExplicitRoyaltyEmpty = Arr::get($args, 'mutation.explicitRoyaltyCurrencies') === [];
        $explicitRoyaltyRules = ['nullable', 'bail', 'array', 'min:0', 'max:10', new DistinctMultiAsset()];

        return [
            'mutation.owner' => $this->mutationOwnerRule($isOwnerEmpty, $isExplicitRoyaltyEmpty),
            'mutation.royalty' => $isExplicitRoyaltyEmpty ? [] : ['required_without_all:mutation.owner,mutation.explicitRoyaltyCurrencies'],
            'mutation.royalty.beneficiary' => ['nullable', 'bail', 'required_with:mutation.royalty.percentage', new ValidSubstrateAccount()],
            'mutation.royalty.percentage' => ['required_with:mutation.royalty.beneficiary', new ValidRoyaltyPercentage()],
            'mutation.explicitRoyaltyCurrencies' => $isExplicitRoyaltyEmpty ? $explicitRoyaltyRules : [...$explicitRoyaltyRules, 'required_without_all:mutation.owner,mutation.royalty'],
            ...$this->getTokenFieldRules('mutation.explicitRoyaltyCurrencies.*', $args),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new IsCollectionOwner()],
        ];
    }

    /**
     * Get the owner attribute validation rules.
     */
    protected function mutationOwnerRule(bool $isOwnerEmpty, bool $isExplicitRoyaltyEmpty): array
    {
        $ownerRules = ['nullable', 'bail', new ValidSubstrateAccount()];

        if ($isOwnerEmpty) {
            return [...$ownerRules, 'filled'];
        }

        if ($isExplicitRoyaltyEmpty) {
            return $ownerRules;
        }

        return [...$ownerRules, 'required_without_all:mutation.royalty,mutation.explicitRoyaltyCurrencies'];
    }
}
