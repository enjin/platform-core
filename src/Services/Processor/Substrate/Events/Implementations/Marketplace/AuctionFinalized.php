<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Marketplace;

use Carbon\Carbon;
use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Events\Substrate\Marketplace\AuctionFinalized as AuctionFinalizedEvent;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Models\MarketplaceSale;
use Enjin\Platform\Models\MarketplaceState;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace\AuctionFinalized as AuctionFinalizedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\MarketplaceSubstrateEvent;
use Illuminate\Support\Facades\Log;

class AuctionFinalized extends MarketplaceSubstrateEvent
{
    /** @var AuctionFinalizedPolkadart */
    protected Event $event;

    protected ?MarketplaceSale $saleCreated = null;

    /**
     * Handles the auction finalized event.
     */
    #[\Override]
    public function run(): void
    {
        try {
            // Fails if the listing is not found
            $listing = $this->getListing($this->event->listingId);
            $bidder = $this->firstOrStoreAccount($this->event->winningBidder);
            $seller = Wallet::find($listing->seller_wallet_id);

            MarketplaceState::create([
                'marketplace_listing_id' => $listing->id,
                'state' => ListingState::FINALIZED->name,
                'height' => $this->block->number,
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ]);

            $this->saleCreated = MarketplaceSale::create([
                'listing_chain_id' => $listing->listing_chain_id,
                'wallet_id' => $bidder->id,
                'price' => $this->event->price,
                'amount' => $listing->amount,
            ]);

            $this->extra = [
                'collection_id' => $listing->make_collection_chain_id,
                'token_id' => $listing->make_token_chain_id,
                'bidder' => $bidder->public_key,
                'seller' => $seller->public_key,
            ];
        } catch (\Throwable) {
            Log::error(
                sprintf(
                    'Listing %s was finalized but could not be found in the database.',
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
                'Listing %s was finalized with a sale from %s.',
                $this->event->listingId,
                $this->event->winningBidder,
            )
        );

    }

    #[\Override]
    public function broadcast(): void
    {
        AuctionFinalizedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
            $this->saleCreated,
        );
    }
}
