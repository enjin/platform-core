<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class CounterOfferRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $creator;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 'ListingIdOf<T>')) ? $value : HexConverter::bytesToHex($value));
        $self->creator = Account::parseAccount($self->getValue($data, 'T::AccountId'));

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
            ['type' => 'creator', 'value' => $this->creator],
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
      "CounterOfferRemoved" => array:2 [▼
        "ListingIdOf<T>" => array:32 [▼
          0 => 43
          1 => 248
          2 => 224
          3 => 145
          4 => 244
          5 => 230
          6 => 37
          7 => 161
          8 => 30
          9 => 116
          10 => 250
          11 => 87
          12 => 38
          13 => 200
          14 => 50
          15 => 167
          16 => 145
          17 => 233
          18 => 94
          19 => 9
          20 => 2
          21 => 81
          22 => 124
          23 => 155
          24 => 243
          25 => 197
          26 => 108
          27 => 159
          28 => 94
          29 => 222
          30 => 252
          31 => 144
        ]
        "T::AccountId" => array:32 [▼
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
      ]
    ]
  ]
  "topics" => []
]
*/
