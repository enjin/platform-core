<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Services\Database\MetadataService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RefreshMetadataMutation extends Mutation implements PlatformGraphQlMutation
{
    use HasEncodableTokenId;
    use HasTokenIdFieldRules;
    use HasTokenIdFields;
    use InPrimarySubstrateSchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'RefreshMetadata',
            'description' => __('enjin-platform::mutation.refresh_metadata.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.approve_collection.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::args.common.tokenId'), true),
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
        MetadataService $metadataService,
    ): mixed {
        Attribute::query()
            ->select('key', 'value', 'token_id', 'collection_id')
            ->with([
                'token:id,collection_id,token_chain_id',
                'collection:id,collection_chain_id',
            ])->whereHas(
                'collection',
                fn ($query) => $query->where('collection_chain_id', Arr::get($args, 'collectionId'))
            )->when(
                $tokenId = $this->encodeTokenId($args),
                fn ($query) => $query->whereHas(
                    'token',
                    fn ($query) => $query->where('token_chain_id', $tokenId)
                )
            )
            ->get()
            ->each(fn ($attribute) => $metadataService->fetchAndCache($attribute->value_string));

        return true;
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [
                new MinBigInt(0),
                new MaxBigInt(Hex::MAX_UINT128),
                Rule::exists('collections', 'collection_chain_id'),
            ],
            ...$this->getOptionalTokenFieldRulesExist(),
        ];
    }
}
