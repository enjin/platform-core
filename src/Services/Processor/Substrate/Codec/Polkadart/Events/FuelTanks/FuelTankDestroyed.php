<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class FuelTankDestroyed extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, ['tank_id', 'T::AccountId']));

        return $self;
    }

    public function getParams(): array
    {
        return [
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
        "FuelTankDestroyed" => array:1 [▼
          "T::AccountId" => array:32 [▼
            0 => 2
            1 => 237
            2 => 43
            3 => 57
            4 => 28
            5 => 39
            6 => 85
            7 => 106
            8 => 76
            9 => 108
            10 => 189
            11 => 241
            12 => 198
            13 => 103
            14 => 34
            15 => 131
            16 => 245
            17 => 116
            18 => 175
            19 => 192
            20 => 108
            21 => 155
            22 => 70
            23 => 93
            24 => 205
            25 => 37
            26 => 28
            27 => 79
            28 => 22
            29 => 58
            30 => 166
            31 => 36
          ]
        ]
      ]
    ]
    "topics" => []
  ]
 */
