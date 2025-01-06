<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class ExpiredListingRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 'ListingIdOf<T>')) ? $value : HexConverter::bytesToHex($value));

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
      "ExpiredListingRemoved" => array:1 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 241
          1 => 164
          2 => 221
          3 => 114
          4 => 6
          5 => 169
          6 => 172
          7 => 194
          8 => 210
          9 => 138
          10 => 199
          11 => 221
          12 => 18
          13 => 52
          14 => 254
          15 => 38
          16 => 239
          17 => 117
          18 => 234
          19 => 87
          20 => 62
          21 => 59
          22 => 90
          23 => 77
          24 => 121
          25 => 241
          26 => 218
          27 => 38
          28 => 9
          29 => 254
          30 => 0
          31 => 147
        ]
      ]
    ]
  ]
  "topics" => []
]
*/
