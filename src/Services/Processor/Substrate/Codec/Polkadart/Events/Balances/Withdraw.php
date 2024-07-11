<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Withdraw extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $who;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->who = Account::parseAccount($self->getValue($data, ['who', 'T::AccountId']));
        $self->amount = $self->getValue($data, ['amount', 'T::Balance']);

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'who', 'value' => $this->who],
            ['type' => 'amount', 'value' => $this->amount],
        ];
    }
}

/*
  [▼
    "phase" => array:1 [▼
      "ApplyExtrinsic" => 2
    ]
    "event" => array:1 [▼
      "Balances" => array:1 [▼
        "Withdraw" => array:2 [▼
          "T::AccountId" => array:32 [▼
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
          "T::Balance" => "4114591190625145"
        ]
      ]
    ]
    "topics" => []
  ]
 */
