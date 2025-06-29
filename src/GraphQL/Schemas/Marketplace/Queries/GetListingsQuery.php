<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Models\Indexer\Listing;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetListingsQuery extends MarketplaceQuery
{
    use HasEncodableTokenId;
    use HasTokenIdFieldRules;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetListings',
            'description' => __('enjin-platform-marketplace::query.get_listings.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[Override]
    public function type(): Type
    {
        return GraphQL::paginate('MarketplaceListing', 'MarketplaceListingConnection');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform-marketplace::type.marketplace_bid.field.id'),
            ],
            'listingIds' => [
                'type' => GraphQL::type('[String!]'),
                'deprecationReason' => '',
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
            ],
            'account' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform-marketplace::query.get_listings.args.account'),
            ],
            'makeAssetId' => [
                'type' => GraphQL::type('MultiTokenIdInput'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.makeAssetId'),
            ],
            'takeAssetId' => [
                'type' => GraphQL::type('MultiTokenIdInput'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.takeAssetId'),
            ],
            'collectionIds' => [
                'type' => GraphQL::type('[BigInt]'),
                'description' => __('enjin-platform::input_type.multi_token_id.field.collectionId'),
                'rules' => [new MinBigInt(), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'states' => [
                'type' => GraphQL::type('[ListingStateEnum!]'),
                'description' => __('enjin-platform-marketplace::type.listing_state.description'),
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return Listing::loadSelectFields($resolveInfo, $this->name)
            ->when(
                $ids = Arr::get($args, 'ids'),
                fn ($query) => $query->whereIn('id', $ids)
            )->when(
                $listingIds = Arr::get($args, 'listingIds'),
                fn ($query) => $query->whereIn('listing_chain_id', $listingIds)
            )->when(
                $account = Arr::get($args, 'account'),
                fn ($query) => $query->whereHas(
                    'seller',
                    fn ($query) => $query->where('public_key', SS58Address::getPublicKey($account))
                )
            )->when(
                $makeAsset = Arr::get($args, 'makeAssetId'),
                fn ($query) => $query->where([
                    'make_collection_chain_id' =>  Arr::get($makeAsset, 'collectionId'),
                    'make_token_chain_id' => $this->encodeTokenId(Arr::get($args, 'makeAssetId')),
                ])
            )->when(
                $takeAsset = Arr::get($args, 'takeAssetId'),
                fn ($query) => $query->where([
                    'take_collection_chain_id' =>  Arr::get($takeAsset, 'collectionId'),
                    'take_token_chain_id' => $this->encodeTokenId(Arr::get($args, 'takeAssetId')),
                ])
            )->when(
                $collectionId = Arr::get($args, 'collectionIds'),
                fn ($query) => $query->where(
                    fn ($subquery) => $subquery->whereIn('make_collection_chain_id', $collectionId)
                        ->orWhereIn('take_collection_chain_id', $collectionId)
                )
            )
            ->when(
                $states = Arr::get($args, 'states'),
                fn ($query) => $query->whereHas('state', fn ($query) => $query->whereIn('state', $states))
            )
            ->cursorPaginateWithTotalDesc('marketplace_listings.id', $args['first']);
    }

    /**
     * Get the mutation's request validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'ids' => [
                'bail',
                'array',
                'prohibits:listingIds',
                'max:1000',
            ],
            'listingIds' => [
                'bail',
                'array',
                'prohibits:ids',
                'max:1000',
            ],
            'account' => [
                'bail',
                'max:255',
                new ValidSubstrateAddress(),
            ],
            'makeAssetId.collectionId' => [
                'bail',
                'required_with:makeAssetId.tokenId',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
            ...$this->getOptionalTokenFieldRules('makeAssetId'),
            'takeAssetId.collectionId' => [
                'bail',
                'required_with:takeAssetId.tokenId',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
            ...$this->getOptionalTokenFieldRules('takeAssetId'),
        ];
    }
}
