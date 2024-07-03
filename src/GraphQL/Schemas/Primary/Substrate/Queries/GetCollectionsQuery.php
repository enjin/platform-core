<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetCollectionsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the query's attributes.
     */
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
    public function args(): array
    {
        return ConnectionInput::args([
            'collectionIds' => [
                'type' => GraphQL::type('[BigInt]'),
                'description' => __('enjin-platform::query.get_collections.args.collectionIds'),
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve(
        $root,
        $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
    ): mixed {
        return Collection::loadSelectFields($resolveInfo, $this->name)
            ->addSelect(DB::raw('cast(collection_chain_id as unsigned integer) as collection_id'))
            ->when(!empty($args['collectionIds']), fn (Builder $query) => $query->whereIn('collection_chain_id', $args['collectionIds']))
            ->cursorPaginateWithTotalDesc('collection_id', $args['first']);
    }

    /**
     * Get the validatio rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'collectionIds' => ['nullable', 'bail', 'array', 'min:0', 'max:100', 'distinct'],
            'collectionIds.*' => [new MinBigInt(2000), new MaxBigInt(Hex::MAX_UINT128)],
        ];
    }
}
