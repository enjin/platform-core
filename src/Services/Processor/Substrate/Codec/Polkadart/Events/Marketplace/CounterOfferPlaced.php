<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class CounterOfferPlaced extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $counterOffer;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, ['listing_id', 'ListingIdOf<T>'])) ? $value : HexConverter::bytesToHex($value));
        $self->counterOffer = $self->getValue($data, ['CounterOffer<T>']);

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
            ['type' => 'counter_offer', 'value' => $this->counterOffer],
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
      "CounterOfferPlaced" => array:2 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 87
          1 => 182
          2 => 160
          3 => 167
          4 => 171
          5 => 156
          6 => 155
          7 => 30
          8 => 16
          9 => 44
          10 => 112
          11 => 175
          12 => 95
          13 => 30
          14 => 203
          15 => 8
          16 => 9
          17 => 26
          18 => 93
          19 => 247
          20 => 240
          21 => 120
          22 => 55
          23 => 147
          24 => 71
          25 => 178
          26 => 236
          27 => 70
          28 => 68
          29 => 174
          30 => 246
          31 => 35
        ]
        "CounterOffer<T>" => array:3 [▼
          "seller_price" => "2000000000000"
          "buyer_price" => null
          "deposit" => array:2 [▼
            "depositor" => array:32 [▼
              0 => 212
              1 => 53
              2 => 147
              3 => 199
              4 => 21
              5 => 253
              6 => 211
              7 => 28
              8 => 97
              9 => 20
              10 => 26
              11 => 189
              12 => 4
              13 => 169
              14 => 159
              15 => 214
              16 => 130
              17 => 44
              18 => 133
              19 => 88
              20 => 133
              21 => 76
              22 => 205
              23 => 227
              24 => 154
              25 => 86
              26 => 132
              27 => 231
              28 => 165
              29 => 109
              30 => 162
              31 => 125
            ]
            "amount" => "100000000000000000"
          ]
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
