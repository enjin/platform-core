<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\Models\Indexer\Bid;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetBidQuery extends MarketplaceQuery
{
    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetBid',
            'description' => __('enjin-platform-marketplace::query.get_bid.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[Override]
    public function type(): Type
    {
        return GraphQL::type('MarketplaceBid');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Bid::selectFields($getSelectFields)
            ->where('id', $args['id'])
            ->first();
    }
}
