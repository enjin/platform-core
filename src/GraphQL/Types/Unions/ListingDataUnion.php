<?php

namespace Enjin\Platform\GraphQL\Types\Unions;

use Enjin\Platform\Enums\Substrate\ListingType;
use Enjin\Platform\Interfaces\PlatformGraphQlUnion;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;

class ListingDataUnion extends UnionType implements PlatformGraphQlUnion
{
    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ListingData',
            'description' => __('enjin-platform-marketplace::union.listing_data.description'),
        ];
    }

    /**
     * The possible types that this union can be.
     */
    #[\Override]
    public function types(): array
    {
        return [
            GraphQL::type('FixedPriceData'),
            GraphQL::type('AuctionData'),
            GraphQL::type('OfferData'),
        ];
    }

    /**
     * Resolves concrete ObjectType for given object value.
     */
    public function resolveType($objectValue, $context, ResolveInfo $info)
    {
        return match ($objectValue?->type) {
            ListingType::FIXED_PRICE->name => GraphQL::type('FixedPriceData'),
            ListingType::AUCTION->name => GraphQL::type('AuctionData'),
            ListingType::OFFER->name => GraphQL::type('OfferData'),
            default => null,
        };
    }
}