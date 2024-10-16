<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class ListingRemovedUnderMinimum extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 'ListingIdOf<T>')) ? $value : HexConverter::bytesToHex($value));

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
      "ListingRemovedUnderMinimum" => array:1 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 89
          1 => 25
          2 => 29
          3 => 140
          4 => 104
          5 => 208
          6 => 132
          7 => 200
          8 => 209
          9 => 102
          10 => 64
          11 => 249
          12 => 73
          13 => 41
          14 => 178
          15 => 97
          16 => 41
          17 => 37
          18 => 107
          19 => 119
          20 => 10
          21 => 222
          22 => 125
          23 => 173
          24 => 130
          25 => 232
          26 => 94
          27 => 3
          28 => 133
          29 => 31
          30 => 249
          31 => 91
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
