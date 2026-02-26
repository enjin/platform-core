<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasAdhocRules;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\TokenGroup;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokenGroupQuery extends Query implements PlatformGraphQlQuery
{
    use HasAdhocRules;
    use InPrimarySubstrateSchema;

    /**
     * Get the query's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetTokenGroup',
            'description' => __('enjin-platform::query.get_token_group.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('TokenGroup');
    }

    /**
     * Get the query's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::query.get_token_group.args.collectionId'),
                'rules' => ['exists:collections,collection_chain_id'],
            ],
            'tokenGroupId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::query.get_token_group.args.tokenGroupId'),
            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return TokenGroup::loadSelectFields($resolveInfo, $this->name)
            ->whereHas('collection', fn ($query) => $query->where('collection_chain_id', $args['collectionId']))
            ->where('token_group_chain_id', $args['tokenGroupId'])
            ->first();
    }
}
