<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetCollectionsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
        SingleFilterOnly::class,
    ];

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetCollections',
            'description' => __('enjin-platform::query.get_collections.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Collection', 'CollectionConnection');
    }

    /**
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String]'),
                'description' => '',
                'singleFilter' => true,
            ],
            'collectionIds' => [
                'type' => GraphQL::type('[BigInt]'),
                'description' => __('enjin-platform::query.get_collections.args.collectionIds'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Collection::selectFields($getSelectFields)
            ->whereIn('id', $args['ids'] ?? $args['collectionIds'])
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Get the validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'collectionIds' => ['nullable', 'array', 'min:0', 'max:100', 'distinct'],
            'collectionIds.*' => [new MinBigInt(2000), new MaxBigInt(Hex::MAX_UINT128)],
        ];
    }
}
