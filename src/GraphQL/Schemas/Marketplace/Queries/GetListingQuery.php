<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\Models\MarketplaceListing;
use Enjin\Platform\Rules\ListingExists;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetListingQuery extends MarketplaceQuery
{
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
        return GraphQL::type('MarketplaceListing!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'listingId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields
    ) {
        return MarketplaceListing::loadSelectFields($resolveInfo, $this->name)
            ->when(
                $id = Arr::get($args, 'id'),
                fn ($query) => $query->where('id', $id)
            )->when(
                $listingId = Arr::get($args, 'listingId'),
                fn ($query) => $query->where('listing_chain_id', $listingId)
            )->first();
    }

    /**
     * Get the mutation's request validation rules.
     */
    #[\Override]
    protected function rules(array $args = []): array
    {
        return [
            'id' => [
                'bail',
                'required_without:listingId',
                new MinBigInt(),
                new MaxBigInt(),
                new ListingExists('id'),
            ],
            'listingId' => [
                'bail',
                'required_without:id',
                'max:255',
                new ListingExists(),
            ],
        ];
    }
}
