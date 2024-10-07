<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class FreezeStateMutated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly ?int $ruleSetId;
    public readonly bool $isFrozen;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, 'T::AccountId'));
        $self->ruleSetId = $self->getValue($data, 'Option<T::RuleSetId>');
        $self->isFrozen = $self->getValue($data, 'bool') ?? false;

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'ruleSetId', 'value' => $this->ruleSetId],
            ['type' => 'isFrozen', 'value' => $this->isFrozen],
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
        "FreezeStateMutated" => array:3 [▼
          "T::AccountId" => array:32 [▼
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
          "Option<T::RuleSetId>" => 0
          "bool" => true
        ]
      ]
    ]
    "topics" => []
  ]
 */
