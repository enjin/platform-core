<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\Models\Indexer\Sale;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetSaleQuery extends MarketplaceQuery
{
    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetSale',
            'description' => __('enjin-platform-marketplace::query.get_sale.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[Override]
    public function type(): Type
    {
        return GraphQL::type('MarketplaceSale');
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
        return Sale::selectFields($getSelectFields)
            ->where('id', $args['id'])
            ->first();
    }
}
