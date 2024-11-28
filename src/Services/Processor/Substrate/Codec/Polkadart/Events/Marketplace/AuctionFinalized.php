<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class AuctionFinalized extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly ?string $winningBidder;
    public readonly ?string $price;
    public readonly string $protocolFee;
    public readonly string $royalty;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, 0)) ? $value : HexConverter::bytesToHex($value));
        $self->winningBidder = Account::parseAccount($self->getValue($data, '1.bidder'));
        $self->price = $self->getValue($data, '1.price');
        $self->protocolFee = $self->getValue($data, 2);
        $self->royalty = $self->getValue($data, 3);

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'listing_id', 'value' => $this->listingId],
            ['type' => 'winning_bidder', 'value' => $this->winningBidder],
            ['type' => 'price', 'value' => $this->price],
            ['type' => 'protocol_fee', 'value' => $this->protocolFee],
            ['type' => 'royalty', 'value' => $this->royalty],
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
        "AuctionFinalized" => array:4 [▼
          0 => array:32 [▼
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
          1 => array:2 [▼
            "bidder" => array:32 [▼
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
            "price" => "10000000"
          ]
          2 => "250000"
          3 => "0"
        ]
      ]
    ]
    "topics" => []
  ]
*/
