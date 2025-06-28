<?php

namespace Enjin\Platform\Services;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Listing;
use Enjin\Platform\Models\Token;
use Illuminate\Database\Eloquent\Model;

class MarketplaceService
{
    /**
     * Get the collection by column and value.
     */
    public function get(string $index, string $column = 'listing_chain_id'): Model
    {
        return Listing::where($column, '=', $index)->firstOrFail();
    }

    public function getRoyaltyBeneficiaryCount(string $listingId): int
    {
        $royaltyCount = 0;

        $listing = Listing::firstWhere('id', $listingId);
        $makeCollection = Collection::firstWhere('collection_chain_id', $listing?->make_collection_chain_id);
        $makeToken = Token::firstWhere(['collection_id' => $makeCollection?->id, 'token_chain_id' => $listing?->make_token_chain_id]);

        if ($makeCollection?->royalty_wallet_id !== null || $makeToken?->royalty_wallet_id !== null) {
            $royaltyCount = 1;
        }

        return $royaltyCount;
    }
}
