<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\Models\Listing;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetListingQuery extends MarketplaceQuery
{
    protected $middleware = [
        SingleFilterOnly::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetListing',
            'description' => __('enjin-platform-marketplace::query.get_listing.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[\Override]
    public function type(): Type
    {
        return GraphQL::type('MarketplaceListing');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
                'singleFilter' => true,
            ],
            'listingId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
                'singleFilter' => true,
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Listing::selectFields($getSelectFields)
            ->where('id', $args['id'] ?? $args['listingId'])
            ->first();
    }
}
