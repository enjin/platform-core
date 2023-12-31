<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class AuctionFinalized implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly ?string $winningBidder;
    public readonly ?string $price;
    public readonly string $protocolFee;
    public readonly string $royalty;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = Arr::get($data, 'event.Marketplace.AuctionFinalized.listing_id');
        $self->winningBidder = Arr::get($data, 'event.Marketplace.AuctionFinalized.winning_bid.Some.bidder');
        $self->price = Arr::get($data, 'event.Marketplace.AuctionFinalized.winning_bid.Some.price');
        $self->protocolFee = Arr::get($data, 'event.Marketplace.AuctionFinalized.protocol_fee');
        $self->royalty = Arr::get($data, 'event.Marketplace.AuctionFinalized.royalty');

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
            ['type' => 'winning_bidder', 'value' => $this->winningBidder],
            ['type' => 'price', 'value' => $this->price],
            ['type' => 'protocol_fee', 'value' => $this->protocolFee],
            ['type' => 'royalty', 'value' => $this->royalty],
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 6
        },
        "event": {
            "Marketplace": {
                "AuctionFinalized": {
                    "listing_id": "5abb7f8eb36bfa505e43564d5b9d8657d75537d7509e4683442e41209ba9a326",
                    "winning_bid": {
                        "Some": {
                            "bidder": "1e7462f65c593827ea042101d5d2befdb877883ce72b363b33d46a2a054d4a52",
                            "price": "1000000000000000000"
                        }
                    },
                    "protocol_fee": "25000000000000000",
                    "royalty": "0"
                }
            }
        },
        "topics": []
    },
*/
