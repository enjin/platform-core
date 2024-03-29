<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasAdhocRules;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Collection;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetCollectionQuery extends Query implements PlatformGraphQlQuery
{
    use HasAdhocRules;
    use InPrimarySubstrateSchema;

    /**
     * Get the query's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'GetCollection',
            'description' => __('enjin-platform::query.get_collection.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Collection!');
    }

    /**
     * Get the query's arguments definition.
     */
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::query.get_collection.args.collectionId'),
                'rules' => ['exists:collections,collection_chain_id'],
            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Collection::loadSelectFields($resolveInfo, $this->name)
            ->where('collection_chain_id', $args['collectionId'])
            ->first();
    }
}
