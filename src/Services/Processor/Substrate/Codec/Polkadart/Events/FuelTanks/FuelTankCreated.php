<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class FuelTankCreated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $owner;
    public readonly string $tankName;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->owner = Account::parseAccount($self->getValue($data, 0));
        $self->tankName = HexConverter::hexToString(is_string($value = $self->getValue($data, 1)) ? $value : HexConverter::bytesToHex($value));
        $self->tankId = HexConverter::prefix(is_string($value = $self->getValue($data, 2)) ? $value : HexConverter::bytesToHex($value));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'owner', 'value' => $this->owner],
            ['type' => 'tankName', 'value' => $this->tankName],
            ['type' => 'tankId', 'value' => $this->tankId],
        ];
    }
}

/* Example 1
[▼
    "phase" => array:1 [▼
      "ApplyExtrinsic" => 2
    ]
    "event" => array:1 [▼
      "FuelTanks" => array:1 [▼
        "FuelTankCreated" => array:3 [▼
          0 => array:32 [▼
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
          1 => array:13 [▼
            0 => 84
            1 => 104
            2 => 101
            3 => 32
            4 => 70
            5 => 117
            6 => 101
            7 => 108
            8 => 32
            9 => 84
            10 => 97
            11 => 110
            12 => 107
          ]
          2 => array:32 [▼
            0 => 140
            1 => 184
            2 => 230
            3 => 192
            4 => 80
            5 => 13
            6 => 8
            7 => 132
            8 => 49
            9 => 34
            10 => 135
            11 => 124
            12 => 42
            13 => 192
            14 => 250
            15 => 84
            16 => 54
            17 => 112
            18 => 201
            19 => 96
            20 => 152
            21 => 168
            22 => 6
            23 => 104
            24 => 223
            25 => 99
            26 => 109
            27 => 254
            28 => 59
            29 => 148
            30 => 159
            31 => 19
          ]
        ]
      ]
    ]
    "topics" => []
  ]
 */
