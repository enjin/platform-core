<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class ListingCancelled extends Event implements PolkadartEvent
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
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, ['listing_id', 'ListingIdOf<T>'])) ? $value : HexConverter::bytesToHex($value));

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
[▼
  "phase" => array:1 [▼
    "ApplyExtrinsic" => 2
  ]
  "event" => array:1 [▼
    "Marketplace" => array:1 [▼
      "ListingCancelled" => array:1 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 149
          1 => 73
          2 => 178
          3 => 222
          4 => 67
          5 => 104
          6 => 222
          7 => 175
          8 => 149
          9 => 83
          10 => 84
          11 => 31
          12 => 134
          13 => 78
          14 => 114
          15 => 8
          16 => 129
          17 => 106
          18 => 15
          19 => 118
          20 => 41
          21 => 217
          22 => 140
          23 => 9
          24 => 96
          25 => 137
          26 => 32
          27 => 181
          28 => 230
          29 => 57
          30 => 191
          31 => 199
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
