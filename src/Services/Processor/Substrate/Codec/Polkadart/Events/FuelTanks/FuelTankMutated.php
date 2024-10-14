<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class FuelTankMutated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;

    public readonly ?array $userAccountManagement;
    public readonly ?string $coveragePolicy;
    public readonly ?array $accountRules;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, 'T::AccountId'));

        $self->userAccountManagement = is_bool($b = $self->getValue($data, 'T::TankMutation.user_account_management.SomeMutation'))
            ? ['tankReservesAccountCreationDeposit' => $b]
            : $b;

        $self->coveragePolicy = $self->getValue($data, 'T::TankMutation.coverage_policy');
        $self->accountRules = $self->getValue($data, 'T::TankMutation.account_rules');

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userAccountManagement', 'value' => $this->userAccountManagement],
            ['type' => 'coveragePolicy', 'value' => $this->coveragePolicy],
            ['type' => 'accountRules', 'value' => $this->accountRules],
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
        "FuelTankMutated" => array:2 [▼
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
          "T::TankMutation" => array:3 [▼
            "user_account_management" => array:1 [▼
              "SomeMutation" => true
            ]
            "coverage_policy" => "FeesAndDeposit"
            "account_rules" => array:1 [▼
              0 => array:1 [▼
                "WhitelistedCallers" => array:2 [▼
                  0 => array:32 [▼
                    0 => 142
                    1 => 175
                    2 => 4
                    3 => 21
                    4 => 22
                    5 => 135
                    6 => 115
                    7 => 99
                    8 => 38
                    9 => 201
                    10 => 254
                    11 => 161
                    12 => 126
                    13 => 37
                    14 => 252
                    15 => 82
                    16 => 135
                    17 => 97
                    18 => 54
                    19 => 147
                    20 => 201
                    21 => 18
                    22 => 144
                    23 => 156
                    24 => 178
                    25 => 38
                    26 => 170
                    27 => 71
                    28 => 148
                    29 => 242
                    30 => 106
                    31 => 72
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
                ]
              ]
            ]
          ]
        ]
      ]
    ]
    "topics" => []
  ]
 */
