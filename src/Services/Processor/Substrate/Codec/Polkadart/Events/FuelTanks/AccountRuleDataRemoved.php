<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class AccountRuleDataRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;
    public readonly int $ruleSetId;
    public readonly string $ruleKind;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, 0));
        $self->userId = Account::parseAccount($self->getValue($data, 1));
        $self->ruleSetId = $self->getValue($data, 2);
        $self->ruleKind = $self->getValue($data, 3);

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userId', 'value' => $this->userId],
            ['type' => 'ruleSetId', 'value' => $this->ruleSetId],
            ['type' => 'ruleKind', 'value' => $this->ruleKind],
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
        "AccountRuleDataRemoved" => array:4 [▼
          0 => array:32 [▼
            0 => 123
            1 => 78
            2 => 1
            3 => 154
            4 => 122
            5 => 43
            6 => 8
            7 => 66
            8 => 142
            9 => 208
            10 => 141
            11 => 164
            12 => 0
            13 => 53
            14 => 1
            15 => 180
            16 => 114
            17 => 189
            18 => 231
            19 => 237
            20 => 254
            21 => 243
            22 => 194
            23 => 222
            24 => 207
            25 => 97
            26 => 139
            27 => 77
            28 => 181
            29 => 180
            30 => 22
            31 => 179
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
          2 => 0
          3 => "UserFuelBudget"
        ]
      ]
    ]
    "topics" => []
  ]
 */
