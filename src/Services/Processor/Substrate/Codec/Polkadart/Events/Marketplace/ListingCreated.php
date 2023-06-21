<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class ListingCreated implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $seller;
    public readonly array $makeAssetId;
    public readonly array $takeAssetId;
    public readonly string $amount;
    public readonly string $price;
    public readonly string $minTakeValue;
    public readonly string $feeSide;
    public readonly int $creationBlock;
    public readonly string $deposit;
    public readonly array $salt;
    public readonly string|array $data;
    public readonly array $state;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = Arr::get($data, 'event.Marketplace.ListingCreated.listing_id');
        $self->seller = Arr::get($data, 'event.Marketplace.ListingCreated.listing.seller');
        $self->makeAssetId = Arr::get($data, 'event.Marketplace.ListingCreated.listing.make_asset_id');
        $self->takeAssetId = Arr::get($data, 'event.Marketplace.ListingCreated.listing.take_asset_id');
        $self->amount = Arr::get($data, 'event.Marketplace.ListingCreated.listing.amount');
        $self->price = Arr::get($data, 'event.Marketplace.ListingCreated.listing.price');
        $self->minTakeValue = Arr::get($data, 'event.Marketplace.ListingCreated.listing.min_take_value');
        $self->feeSide = Arr::get($data, 'event.Marketplace.ListingCreated.listing.fee_side');
        $self->creationBlock = Arr::get($data, 'event.Marketplace.ListingCreated.listing.creation_block');
        $self->deposit = Arr::get($data, 'event.Marketplace.ListingCreated.listing.deposit');
        $self->salt = Arr::get($data, 'event.Marketplace.ListingCreated.listing.salt');
        $self->data = Arr::get($data, 'event.Marketplace.ListingCreated.listing.data');
        $self->state = Arr::get($data, 'event.Marketplace.ListingCreated.listing.state');

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
        ];
    }
}

/* Example 1
 {
        "phase": {
            "ApplyExtrinsic": 31
        },
        "event": {
            "Marketplace": {
                "ListingCreated": {
                    "listing_id": "3a9d2f540a276f59104c6c2057903dff1c1d1a481ff87315d4b0017b9d7bed42",
                    "listing": {
                        "seller": "b882d3135b23eefc56ff0fd9e7d3f87c732040b49282cbd836f142c2435c0d11",
                        "make_asset_id": {
                            "collection_id": "89793",
                            "token_id": "0"
                        },
                        "take_asset_id": {
                            "collection_id": "0",
                            "token_id": "0"
                        },
                        "amount": "1",
                        "price": "1",
                        "min_take_value": "0",
                        "fee_side": "Take",
                        "creation_block": 642082,
                        "deposit": "2025700000000000000",
                        "salt": [
                            115,
                            97,
                            108,
                            116,
                            49,
                            50,
                            51
                        ],
                        "data": "FixedPrice",
                        "state": {
                            "FixedPrice": {
                                "amount_filled": "0"
                            }
                        }
                    }
                }
            }
        },
        "topics": []
    },
 */

/* Example 2
   {
        "phase": {
            "ApplyExtrinsic": 34
        },
        "event": {
            "Marketplace": {
                "ListingCreated": {
                    "listing_id": "5abb7f8eb36bfa505e43564d5b9d8657d75537d7509e4683442e41209ba9a326",
                    "listing": {
                        "seller": "e4569fb538b1cb511472919417e748d96aaab546f15d89f3d387122ab72eef79",
                        "make_asset_id": {
                            "collection_id": "89800",
                            "token_id": "0"
                        },
                        "take_asset_id": {
                            "collection_id": "0",
                            "token_id": "0"
                        },
                        "amount": "1",
                        "price": "1000000000000000000",
                        "min_take_value": "975000000000000000",
                        "fee_side": "Take",
                        "creation_block": 642082,
                        "deposit": "2025700000000000000",
                        "salt": [
                            115,
                            97,
                            108,
                            116,
                            49,
                            50,
                            51
                        ],
                        "data": {
                            "Auction": {
                                "start_block": 642088,
                                "end_block": 642092
                            }
                        },
                        "state": {
                            "Auction": {
                                "high_bid": {
                                    "None": null
                                }
                            }
                        }
                    }
                }
            }
        },
        "topics": []
    },
*/
