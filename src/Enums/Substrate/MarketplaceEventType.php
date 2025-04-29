<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\AuctionFinalized;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\BidPlaced;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\ListingCancelled;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\ListingCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\ListingFilled;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace\ListingRemovedUnderMinimum;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum MarketplaceEventType: string
{
    use EnumExtensions;

    case AUCTION_FINALIZED = 'AuctionFinalized';
    case BID_PLACED = 'BidPlaced';
    case LISTING_CANCELLED = 'ListingCancelled';
    case LISTING_CREATED = 'ListingCreated';
    case LISTING_FILLED = 'ListingFilled';
    case LISTING_REMOVED_UNDER_MINIMUM = 'ListingRemovedUnderMinimum';

    /**
     * Get the processor for the event.
     */
    public function getProcessor($event, $block, $codec): SubstrateEvent
    {
        return match ($this) {
            self::AUCTION_FINALIZED => new AuctionFinalized($event, $block, $codec),
            self::BID_PLACED => new BidPlaced($event, $block, $codec),
            self::LISTING_CANCELLED => new ListingCancelled($event, $block, $codec),
            self::LISTING_CREATED => new ListingCreated($event, $block, $codec),
            self::LISTING_FILLED => new ListingFilled($event, $block, $codec),
            self::LISTING_REMOVED_UNDER_MINIMUM => new ListingRemovedUnderMinimum($event, $block, $codec),
        };
    }
}
