<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class AccountRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, 0));
        $self->userId = Account::parseAccount($self->getValue($data, 1));

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userId', 'value' => $this->userId],
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
        "AccountRemoved" => array:2 [▼
          0 => array:32 [▼
            0 => 89
            1 => 184
            2 => 117
            3 => 198
            4 => 158
            5 => 220
            6 => 161
            7 => 224
            8 => 130
            9 => 188
            10 => 156
            11 => 88
            12 => 51
            13 => 69
            14 => 25
            15 => 88
            16 => 225
            17 => 190
            18 => 240
            19 => 218
            20 => 217
            21 => 220
            22 => 14
            23 => 215
            24 => 197
            25 => 225
            26 => 53
            27 => 227
            28 => 59
            29 => 159
            30 => 183
            31 => 137
          ]
          1 => array:32 [▼
            0 => 28
            1 => 189
            2 => 45
            3 => 67
            4 => 83
            5 => 10
            6 => 68
            7 => 112
            8 => 90
            9 => 208
            10 => 136
            11 => 175
            12 => 49
            13 => 62
            14 => 24
            15 => 248
            16 => 11
            17 => 83
            18 => 239
            19 => 22
            20 => 179
            21 => 97
            22 => 119
            23 => 205
            24 => 75
            25 => 119
            26 => 184
            27 => 70
            28 => 242
            29 => 165
            30 => 240
            31 => 124
          ]
        ]
      ]
    ]
    "topics" => []
  ]
 */
