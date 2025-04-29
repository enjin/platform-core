<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Model;

abstract class MarketplaceSubstrateEvent extends SubstrateEvent
{
    /**
     * Returns the listing with the specified listing ID.
     *
     * @throws PlatformException
     */
    protected function getListing(string $listingId): Model
    {
        if (!$listing = MarketplaceListing::where(['listing_chain_id' => $listingId])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_listing', ['class' => self::class, 'listingId' => $listingId]));
        }

        return $listing;
    }
}
