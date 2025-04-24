<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Traits\HasSelectFields;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class MarketplaceListingType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InMarketplaceSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'MarketplaceListing',
            'description' => __('enjin-platform-marketplace::type.marketplace_listing.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'listingId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
                'alias' => 'listing_chain_id',
            ],
            'makeAssetId' => [
                'type' => GraphQL::type('Asset!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.makeAssetId'),
                'resolve' => fn ($listing) => [
                    'collectionId' => $listing->make_collection_chain_id,
                    'tokenId' => $listing->make_token_chain_id,
                ],
                'is_relation' => false,
                'selectable' => false,
                'always' => ['make_collection_chain_id', 'make_token_chain_id'],
            ],
            'takeAssetId' => [
                'type' => GraphQL::type('Asset!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.takeAssetId'),
                'resolve' => fn ($listing) => [
                    'collectionId' => $listing->take_collection_chain_id,
                    'tokenId' => $listing->take_token_chain_id,
                ],
                'is_relation' => false,
                'selectable' => false,
                'always' => ['take_collection_chain_id', 'take_token_chain_id'],
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.amount'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
            ],
            'minTakeValue' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.minTakeValue'),
                'alias' => 'min_take_value',
            ],
            'feeSide' => [
                'type' => GraphQL::type('FeeSide!'),
                'description' => __('enjin-platform-marketplace::enum.fee_side.description'),
                'alias' => 'fee_side',
            ],
            'creationBlock' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.creationBlock'),
                'alias' => 'creation_block',
            ],
            'deposit' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.deposit'),
            ],
            'salt' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.salt'),
            ],
            'state' => [
                'type' => GraphQL::type('ListingState!'),
                'description' => __('enjin-platform-marketplace::type.listing_state.description'),
                'is_relation' => false,
                'selectable' => false,
                'resolve' => fn ($listing) => $listing,
            ],
            'data' => [
                'type' => GraphQL::type('ListingData!'),
                'description' => __('enjin-platform-marketplace::union.listing_data.description'),
                'is_relation' => false,
                'selectable' => false,
                'resolve' => fn ($listing) => $listing,
            ],
            'seller' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.seller'),
                'is_relation' => true,
            ],
            'sales' => [
                'type' => GraphQL::paginate('MarketplaceSale', 'MarketplaceSaleConnection'),
                'description' => __('enjin-platform-marketplace::type.marketplace_sale.description'),
                'args' => ConnectionInput::args(),
                'is_relation' => true,
                'resolve' => fn ($listing, $args) => [
                    'items' => new CursorPaginator(
                        $listing?->sales,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $listing?->sales_count,
                ],
            ],
            'bids' => [
                'type' => GraphQL::paginate('MarketplaceBid', 'MarketplaceBidConnection'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.description'),
                'args' => ConnectionInput::args(),
                'is_relation' => true,
                'resolve' => fn ($listing, $args) => [
                    'items' => new CursorPaginator(
                        $listing?->bids,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $listing?->bids_count,
                ],
            ],
            'states' => [
                'type' => GraphQL::type('[MarketplaceState!]'),
                'description' => __('enjin-platform-marketplace::type.marketplace_state.description'),
                'is_relation' => true,
            ],
        ];
    }
}
