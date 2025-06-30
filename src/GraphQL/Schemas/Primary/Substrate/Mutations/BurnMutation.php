<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
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
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Substrate\BurnParams;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MaxTokenBalance;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BurnMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasEncodableTokenId;
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldRules;
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
            'name' => 'Burn',
            'description' => __('enjin-platform::mutation.burn.description'),
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
                'description' => __('enjin-platform::mutation.common.args.collectionId'),
            ],
            'params' => [
                'type' => GraphQL::type('BurnParamsInput!'),
                'description' => __('enjin-platform::mutation.burn.args.params'),
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
    ): mixed {
        $args['params']['tokenId'] = $this->encodeTokenId($args['params']);
        unset($args['params']['encodeTokenId'], $args['params']['keepAlive']);

        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            burnParams: new BurnParams(...$args['params'])
        ));

        return $this->storeTransaction($args, $encodedData);
    }

    public static function getEncodableParams(...$params): array
    {
        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'params' => Arr::get($params, 'burnParams', new BurnParams(0, 0))->toEncodable(),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        $min = Arr::get($args, 'params.removeTokenStorage', false) ? 0 : 1;

        return [
            'collectionId' => [$min == 0 ? new IsCollectionOwner() : 'exists:collections,collection_chain_id'],
            'params.amount' => [new MinBigInt($min), new MaxTokenBalance()],
            ...$this->getTokenFieldRulesExist('params'),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        $min = Arr::get($args, 'params.removeTokenStorage', false) ? 0 : 1;

        return [
            'collectionId' => [new MinBigInt(2000), new MaxBigInt(Hex::MAX_UINT128)],
            'params.amount' => [new MinBigInt($min), new MaxBigInt(Hex::MAX_UINT128)],
            ...$this->getTokenFieldRules('params'),
        ];
    }
}
