<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class CallDispatched extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $caller;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->caller = Account::parseAccount($self->getValue($data, 0));
        $self->tankId = Account::parseAccount($self->getValue($data, 1));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'caller', 'value' => $this->caller],
            ['type' => 'tankId', 'value' => $this->tankId],
        ];
    }
}

/* Example 1
[▼
    "phase" => array:1 [▶]
    "event" => array:1 [▼
      "FuelTanks" => array:1 [▼
        "CallDispatched" => array:2 [▼
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
          1 => array:32 [▼
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
        ]
      ]
    ]
    "topics" => []
  ]
 */
