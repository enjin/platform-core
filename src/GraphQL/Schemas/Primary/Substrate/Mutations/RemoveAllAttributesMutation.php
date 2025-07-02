<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
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
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RemoveAllAttributesMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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
            'name' => 'RemoveAllAttributes',
            'description' => __('enjin-platform::mutation.remove_all_attributes.description'),
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
                'description' => __('enjin-platform::mutation.remove_all_attributes.args.collectionId'),
            ],
            'attributeCount' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.remove_all_attributes.args.attributeCount'),
            ],
            ...$this->getTokenFields(__('enjin-platform::args.common.tokenId'), true),
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
        if (!Arr::get($args, 'attributeCount')) {
            $args['attributeCount'] = $this->getAttributeCount($args);
            if ($args['attributeCount'] == 0) {
                throw new PlatformException(__('enjin-platform::error.attribute_count_empty'));
            }
        }

        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            tokenId: $this->encodeTokenId($args),
            attributeCount: $args['attributeCount']
        ));

        return $this->storeTransaction($args, $encodedData);
    }

    public static function getEncodableParams(...$params): array
    {
        $tokenId = Arr::get($params, 'tokenId');

        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'attributeCount' => gmp_init(Arr::get($params, 'attributeCount', 0)),
        ];
    }

    /**
     * Query the attribute count.
     */
    protected function getAttributeCount(array $args): int
    {
        $tokenId = $this->encodeTokenId($args);

        return Attribute::whereHas('collection', fn ($sub) => $sub->where('collection_chain_id', $args['collectionId']))
            ->when($tokenId, fn ($query) => $query->whereHas('token', fn ($sub) => $sub->where('token_chain_id', $tokenId)))
            ->unless($tokenId, fn ($query) => $query->whereNull('token_id'))
            ->count();
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['bail', new IsCollectionOwner()],
            'attributeCount' => ['nullable', 'integer', 'min:1', 'max:' . Hex::MAX_UINT32],
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
