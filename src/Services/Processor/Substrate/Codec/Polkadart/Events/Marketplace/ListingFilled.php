<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class ListingFilled extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $listingId;
    public readonly string $buyer;
    public readonly string $amountFilled;
    public readonly string $amountRemaining;
    public readonly string $protocolFee;
    public readonly string $royalty;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->listingId = HexConverter::prefix(is_string($value = $self->getValue($data, ['listing_id', '0'])) ? $value : HexConverter::bytesToHex($value));
        $self->buyer = Account::parseAccount($self->getValue($data, ['buyer', '1']));
        $self->amountFilled = $self->getValue($data, ['amount_filled', '2']);
        $self->amountRemaining = $self->getValue($data, ['amount_remaining', '3']);
        $self->protocolFee = $self->getValue($data, ['protocol_fee', '4']);
        $self->royalty = $self->getValue($data, ['royalty', '5']);

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
            ['type' => 'buyer', 'value' => $this->buyer],
            ['type' => 'amount_filled', 'value' => $this->amountFilled],
            ['type' => 'amount_remaining', 'value' => $this->amountRemaining],
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
        "ListingFilled" => array:6 [▼
          0 => array:32 [▼
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
          1 => array:32 [▼
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
          2 => "1"
          3 => "9"
          4 => "25"
          5 => "10"
        ]
      ]
    ]
    "topics" => []
  ]
*/
