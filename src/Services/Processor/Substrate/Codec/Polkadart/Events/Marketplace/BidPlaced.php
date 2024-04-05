<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class BidPlaced extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $bidder;
    public readonly string $price;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = is_string($value = $self->getValue($data, ['listing_id', 'ListingIdOf<T>'])) ? HexConverter::prefix($value) : HexConverter::bytesToHex($value);
        $self->bidder = Account::parseAccount($self->getValue($data, ['bid.bidder', 'BidOf<T>.bidder']));
        $self->price = $self->getValue($data, ['bid.price', 'BidOf<T>.price']);

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
            ['type' => 'bidder', 'value' => $this->bidder],
            ['type' => 'price', 'value' => $this->price],
        ];
    }
}

/* Example 1
   {
        "phase": {
            "ApplyExtrinsic": 4
        },
        "event": {
            "Marketplace": {
                "BidPlaced": {
                    "listing_id": "5abb7f8eb36bfa505e43564d5b9d8657d75537d7509e4683442e41209ba9a326",
                    "bid": {
                        "bidder": "1e7462f65c593827ea042101d5d2befdb877883ce72b363b33d46a2a054d4a52",
                        "price": "1000000000000000000"
                    }
                }
            }
        },
        "topics": []
    },
*/
