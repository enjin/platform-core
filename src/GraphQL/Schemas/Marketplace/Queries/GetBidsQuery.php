<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Models\Bid;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetBidsQuery extends MarketplaceQuery
{
    protected $middleware = [
        ResolvePage::class,
        SingleFilterOnly::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetBids',
            'description' => __('enjin-platform-marketplace::query.get_bids.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[\Override]
    public function type(): Type
    {
        return GraphQL::paginate('MarketplaceBid', 'MarketplaceBidConnection');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
                'singleFilter' => true,
            ],
            'accounts' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform-marketplace::query.get_listings.args.account'),
                'singleFilter' => true,
            ],
            'listingIds' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Bid::selectFields($getSelectFields)
            ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('id', $args['ids']))
            ->when(!empty($args['accounts']),
                fn (Builder $query) => $query->whereIn('bidder_id',
                    collect($args['accounts'])
                        ->map(fn ($account) => SS58Address::getPublicKey($account))
                        ->toArray()
                )
            )
            ->when(!empty($args['listingIds']), fn (Builder $query) => $query->whereIn('listing_id', $args['listingIds']))
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Get the mutation's request validation rules.
     */
    #[\Override]
    protected function rules(array $args = []): array
    {
        return [
            'ids' => ['bail', 'nullable', 'array', 'max:1000'],
            'listingIds' => ['bail', 'nullable', 'array', 'max:1000'],
            'listingIds.*' => ['max:255'],
            'accounts' => ['bail', 'nullable', 'array', 'max:1000'],
            'accounts.*' => [new ValidSubstrateAddress()],
        ];
    }
}
