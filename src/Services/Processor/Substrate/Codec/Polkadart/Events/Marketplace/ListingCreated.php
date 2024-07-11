<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class ListingCreated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
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
    public readonly string $salt;
    public readonly ?array $data;
    public readonly array $state;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, ['listing_id', 'ListingIdOf<T>'])) ? $value : HexConverter::bytesToHex($value));
        $self->seller = Account::parseAccount($self->getValue($data, ['listing.seller', 'ListingOf<T>.seller']));
        $self->makeAssetId = $self->getValue($data, ['listing.make_asset_id', 'ListingOf<T>.make_asset_id']);
        $self->takeAssetId = $self->getValue($data, ['listing.take_asset_id', 'ListingOf<T>.take_asset_id']);
        $self->amount = $self->getValue($data, ['listing.amount', 'ListingOf<T>.amount']);
        $self->price = $self->getValue($data, ['listing.price', 'ListingOf<T>.price']);
        $self->minTakeValue = $self->getValue($data, ['listing.min_take_value', 'ListingOf<T>.min_take_value']);
        $self->feeSide = $self->getValue($data, ['listing.fee_side', 'ListingOf<T>.fee_side']);
        $self->creationBlock = $self->getValue($data, ['listing.creation_block', 'ListingOf<T>.creation_block']);
        $self->deposit = $self->getValue($data, ['listing.deposit', 'ListingOf<T>.deposit']);
        $self->salt = HexConverter::bytesToHexPrefixed($self->getValue($data, ['listing.salt', 'ListingOf<T>.salt']));
        $self->data = $self->getValue($data, ['listing.data', 'ListingOf<T>.data']);
        $self->state = $self->getValue($data, ['listing.state', 'ListingOf<T>.state']);

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
        ];
    }
}

/* Example 1
  [▼
    "phase" => array:1 [▼
      "ApplyExtrinsic" => 2
    ]
    "event" => array:1 [▼
      "Marketplace" => array:1 [▼
        "ListingCreated" => array:2 [▼
          "ListingIdOf<T>" => array:32 [▼
            0 => 158
            1 => 1
            2 => 223
            3 => 47
            4 => 249
            5 => 64
            6 => 205
            7 => 7
            8 => 53
            9 => 194
            10 => 206
            11 => 230
            12 => 174
            13 => 160
            14 => 252
            15 => 162
            16 => 219
            17 => 98
            18 => 223
            19 => 209
            20 => 195
            21 => 180
            22 => 213
            23 => 252
            24 => 76
            25 => 143
            26 => 84
            27 => 250
            28 => 169
            29 => 19
            30 => 65
            31 => 148
          ]
          "Listing<T>" => array:12 [▼
            "creator" => array:32 [▼
              0 => 144
              1 => 181
              2 => 171
              3 => 32
              4 => 92
              5 => 105
              6 => 116
              7 => 201
              8 => 234
              9 => 132
              10 => 27
              11 => 230
              12 => 136
              13 => 134
              14 => 70
              15 => 51
              16 => 220
              17 => 156
              18 => 168
              19 => 163
              20 => 87
              21 => 132
              22 => 62
              23 => 234
              24 => 207
              25 => 35
              26 => 20
              27 => 100
              28 => 153
              29 => 101
              30 => 254
              31 => 34
            ]
            "make_asset_id" => array:2 [▼
              "collection_id" => "77160"
              "token_id" => "1"
            ]
            "take_asset_id" => array:2 [▼
              "collection_id" => "0"
              "token_id" => "0"
            ]
            "amount" => "10"
            "price" => "1000"
            "min_received" => "9650"
            "fee_side" => "Take"
            "creation_block" => 26065
            "deposit" => array:2 [▼
              "depositor" => array:32 [▶]
              "amount" => "507225000000000000"
            ]
            "salt" => array:2 [▼
              0 => 18
              1 => 52
            ]
            "data" => array:1 [▼
              "FixedPrice" => null
            ]
            "state" => array:1 [▼
              "FixedPrice" => "0"
            ]
          ]
        ]
      ]
    ]
    "topics" => []
  ]
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
