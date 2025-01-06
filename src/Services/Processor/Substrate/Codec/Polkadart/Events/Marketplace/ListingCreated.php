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

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 'ListingIdOf<T>')) ? $value : HexConverter::bytesToHex($value));
        $self->seller = Account::parseAccount($self->getValue($data, 'Listing<T>.creator'));
        $self->makeAssetId = $self->getValue($data, 'Listing<T>.make_asset_id');
        $self->takeAssetId = $self->getValue($data, 'Listing<T>.take_asset_id');
        $self->amount = $self->getValue($data, 'Listing<T>.amount');
        $self->price = $self->getValue($data, 'Listing<T>.price');
        $self->minTakeValue = $self->getValue($data, 'Listing<T>.min_received');
        $self->feeSide = $self->getValue($data, 'Listing<T>.fee_side');
        $self->creationBlock = $self->getValue($data, 'Listing<T>.creation_block');
        $self->deposit = $self->getValue($data, 'Listing<T>.deposit.amount');
        $self->salt = HexConverter::bytesToHexPrefixed($self->getValue($data, 'Listing<T>.salt'));
        $self->data = $self->getValue($data, 'Listing<T>.data');
        $self->state = $self->getValue($data, 'Listing<T>.state');

        return $self;
    }

    #[\Override]
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
          0 => 3
          1 => 114
          2 => 129
          3 => 96
          4 => 248
          5 => 165
          6 => 24
          7 => 89
          8 => 80
          9 => 248
          10 => 46
          11 => 189
          12 => 32
          13 => 58
          14 => 164
          15 => 152
          16 => 45
          17 => 197
          18 => 144
          19 => 120
          20 => 8
          21 => 67
          22 => 126
          23 => 112
          24 => 175
          25 => 192
          26 => 91
          27 => 211
          28 => 192
          29 => 39
          30 => 213
          31 => 130
        ]
        "Listing<T>" => array:12 [▼
          "creator" => array:32 [▼
            0 => 46
            1 => 217
            2 => 157
            3 => 48
            4 => 210
            5 => 2
            6 => 199
            7 => 175
            8 => 68
            9 => 203
            10 => 74
            11 => 94
            12 => 57
            13 => 108
            14 => 241
            15 => 162
            16 => 3
            17 => 26
            18 => 201
            19 => 107
            20 => 1
            21 => 43
            22 => 227
            23 => 51
            24 => 197
            25 => 94
            26 => 71
            27 => 42
            28 => 238
            29 => 114
            30 => 131
            31 => 117
          ]
          "make_asset_id" => array:2 [▼
            "collection_id" => "80103"
            "token_id" => "3"
          ]
          "take_asset_id" => array:2 [▼
            "collection_id" => "0"
            "token_id" => "0"
          ]
          "amount" => "1"
          "price" => "10000000000000000000"
          "min_received" => "9750000000000000000"
          "fee_side" => "Take"
          "creation_block" => 3162820
          "deposit" => array:2 [▼
            "depositor" => array:32 [▼
              0 => 46
              1 => 217
              2 => 157
              3 => 48
              4 => 210
              5 => 2
              6 => 199
              7 => 175
              8 => 68
              9 => 203
              10 => 74
              11 => 94
              12 => 57
              13 => 108
              14 => 241
              15 => 162
              16 => 3
              17 => 26
              18 => 201
              19 => 107
              20 => 1
              21 => 43
              22 => 227
              23 => 51
              24 => 197
              25 => 94
              26 => 71
              27 => 42
              28 => 238
              29 => 114
              30 => 131
              31 => 117
            ]
            "amount" => "507225000000000000"
          ]
          "salt" => array:9 [▼
            0 => 53
            1 => 55
            2 => 54
            3 => 54
            4 => 51
            5 => 48
            6 => 55
            7 => 51
            8 => 51
          ]
          "data" => array:1 [▼
            "Auction" => array:2 [▶]
          ]
          "state" => array:1 [▼
            "Auction" => null
          ]
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
