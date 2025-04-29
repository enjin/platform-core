<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace;

use Carbon\Carbon;
use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Events\Substrate\Marketplace\ListingRemovedUnderMinimum as ListingRemovedUnderMinimumEvent;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Models\MarketplaceState;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace\ListingRemovedUnderMinimum as ListingRemovedUnderMinimumPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\MarketplaceSubstrateEvent;
use Illuminate\Support\Facades\Log;

class ListingRemovedUnderMinimum extends MarketplaceSubstrateEvent
{
    /** @var ListingRemovedUnderMinimumPolkadart */
    protected Event $event;

    /**
     * Handles the listing cancelled event.
     */
    #[\Override]
    public function run(): void
    {
        try {
            // Fails if the listing is not found
            $listing = $this->getListing($this->event->listingId);
            $seller = Wallet::find($listing->seller_wallet_id);

            MarketplaceState::create([
                'marketplace_listing_id' => $listing->id,
                'state' => ListingState::CANCELLED->name,
                'height' => $this->block->number,
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ]);

            $this->extra = [
                'collection_id' => $listing->make_collection_chain_id,
                'token_id' => $listing->make_token_chain_id,
                'seller' => $seller->public_key,
            ];
        } catch (\Throwable) {
            Log::error(
                sprintf(
                    'Listing %s was removed but could not be found in the database.',
                    $this->event->listingId,
                )
            );
        }
    }

    #[\Override]
    public function log(): void
    {
        Log::debug(
            sprintf(
                'Listing %s was removed because of royalties change.',
                $this->event->listingId,
            )
        );
    }

    #[\Override]
    public function broadcast(): void
    {
        ListingRemovedUnderMinimumEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
