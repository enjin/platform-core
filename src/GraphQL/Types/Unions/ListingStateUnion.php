<?php

namespace Enjin\Platform\GraphQL\Types\Unions;

use Enjin\Platform\Enums\Substrate\ListingType;
use Enjin\Platform\Interfaces\PlatformGraphQlUnion;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;

class ListingStateUnion extends UnionType implements PlatformGraphQlUnion
{
    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ListingState',
            'description' => __('enjin-platform-marketplace::enum.listing_state.description'),
        ];
    }

    /**
     * The possible types that this union can be.
     */
    #[\Override]
    public function types(): array
    {
        return [
            GraphQL::type('FixedPriceState'),
            GraphQL::type('AuctionState'),
            GraphQL::type('OfferState'),
        ];
    }

    /**
     * Resolves concrete ObjectType for given object value.
     */
    public function resolveType($objectValue, $context, ResolveInfo $info)
    {
        return match ($objectValue?->type) {
            ListingType::FIXED_PRICE->name => GraphQL::type('FixedPriceState'),
            ListingType::AUCTION->name => GraphQL::type('AuctionState'),
            ListingType::OFFER->name => GraphQL::type('OfferState'),
            default => null,
        };
    }
}
