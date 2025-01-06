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

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 'ListingIdOf<T>')) ? $value : HexConverter::bytesToHex($value));
        $self->bidder = Account::parseAccount($self->getValue($data, 'Bid<T>.bidder'));
        $self->price = $self->getValue($data, 'Bid<T>.price');

        return $self;
    }

    #[\Override]
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
[▼
  "phase" => array:1 [▼
    "ApplyExtrinsic" => 2
  ]
  "event" => array:1 [▼
    "Marketplace" => array:1 [▼
      "BidPlaced" => array:2 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 57
          1 => 116
          2 => 137
          3 => 159
          4 => 32
          5 => 135
          6 => 226
          7 => 53
          8 => 97
          9 => 171
          10 => 138
          11 => 227
          12 => 157
          13 => 252
          14 => 214
          15 => 65
          16 => 207
          17 => 67
          18 => 210
          19 => 101
          20 => 100
          21 => 134
          22 => 230
          23 => 149
          24 => 153
          25 => 36
          26 => 208
          27 => 235
          28 => 193
          29 => 151
          30 => 173
          31 => 133
        ]
        "Bid<T>" => array:2 [▼
          "bidder" => array:32 [▼
            0 => 142
            1 => 175
            2 => 4
            3 => 21
            4 => 22
            5 => 135
            6 => 115
            7 => 99
            8 => 38
            9 => 201
            10 => 254
            11 => 161
            12 => 126
            13 => 37
            14 => 252
            15 => 82
            16 => 135
            17 => 97
            18 => 54
            19 => 147
            20 => 201
            21 => 18
            22 => 144
            23 => 156
            24 => 178
            25 => 38
            26 => 170
            27 => 71
            28 => 148
            29 => 242
            30 => 106
            31 => 72
          ]
          "price" => "1000000"
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
