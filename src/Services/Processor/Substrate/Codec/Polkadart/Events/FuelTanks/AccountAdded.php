<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class AccountAdded extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;
    public readonly string $tankDeposit;
    public readonly string $userDeposit;
    public readonly string $totalReceived;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, 0));
        $self->userId = Account::parseAccount($self->getValue($data, 1));
        $self->tankDeposit = $self->getValue($data, 2);
        $self->userDeposit = $self->getValue($data, 3);
        $self->totalReceived = $self->getValue($data, 4);

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userId', 'value' => $this->userId],
            ['type' => 'tankDeposit', 'value' => $this->tankDeposit],
            ['type' => 'userDeposit', 'value' => $this->userDeposit],
            ['type' => 'totalReceived', 'value' => $this->totalReceived],
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
        "AccountAdded" => array:5 [▼
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
          2 => "10000000000000000"
          3 => "0"
          4 => "0"
        ]
      ]
    ]
    "topics" => []
  ]
 */
