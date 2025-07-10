<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
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
                'description' => __('enjin-platform::query.get_collections.args.collectionIds'),
                'singleFilter' => true,
            ],
            'collectionIds' => [
                'type' => GraphQL::type('[BigInt]'),
                'description' => __('enjin-platform::query.get_collections.args.collectionIds'),
                'deprecationReason' => '',
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
            ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('id', $args['ids']))
            ->when(!empty($args['collectionIds']), fn (Builder $query) => $query->whereIn('id', $args['collectionIds']))
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Get the validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'ids' => ['nullable', 'max:100', 'distinct'],
            'ids.*' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            // TODO: Remove when the collectionIds argument is removed
            'collectionIds' => ['nullable', 'max:100', 'distinct'],
            'collectionIds.*' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
        ];
    }
}
