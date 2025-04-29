<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace;

use Carbon\Carbon;
use Enjin\Platform\Events\Substrate\Marketplace\BidPlaced as BidPlacedEvent;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Models\MarketplaceBid;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace\BidPlaced as BidPlacedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\MarketplaceSubstrateEvent;
use Illuminate\Support\Facades\Log;

class BidPlaced extends MarketplaceSubstrateEvent
{
    /** @var BidPlacedPolkadart */
    protected Event $event;

    protected ?MarketplaceBid $bidCreated = null;

    /**
     * Handles the bid placed event.
     */
    #[\Override]
    public function run(): void
    {
        try {
            // Fails if the listing is not found
            $listing = $this->getListing($this->event->listingId);
            $bidder = $this->firstOrStoreAccount($this->event->bidder);
            $seller = Wallet::find($listing->seller_wallet_id);

            $this->bidCreated = MarketplaceBid::create([
                'marketplace_listing_id' => $listing->id,
                'wallet_id' => $bidder->id,
                'price' => $this->event->price,
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
                    'Listing %s was filled but could not be found in the database.',
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
                '%s placed a bid on listing %s.',
                $this->event->bidder,
                $this->event->listingId,
            )
        );
    }

    #[\Override]
    public function broadcast(): void
    {
        BidPlacedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
            $this->bidCreated,
        );
    }
}
