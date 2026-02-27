<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\TokenGroup;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokenGroupsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the query's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetTokenGroups',
            'description' => __('enjin-platform::query.get_token_groups.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('TokenGroup', 'TokenGroupConnection');
    }

    /**
     * Get the query's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'collectionId' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::query.get_token_groups.args.collectionId'),
                'rules' => ['nullable', 'exists:collections,collection_chain_id'],
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return TokenGroup::loadSelectFields($resolveInfo, $this->name)
            ->when(
                $collectionId = Arr::get($args, 'collectionId'),
                fn ($query) => $query->whereHas(
                    'collection',
                    fn ($query) => $query->where('collection_chain_id', $collectionId)
                )
            )
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }
}
