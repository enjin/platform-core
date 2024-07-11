<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class MutateFuelTank extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "a039eb0199b9fc28c46109d0056a41bdbddbe7ed72f5dad42d31a52542d55c74"
  "extrinsic_length" => 213
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▼
      "Sr25519" => array:64 [▶]
    ]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 12
      ]
      "nonce" => 127
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "mutate_fuel_tank" => array:2 [▼
        "tank_id" => array:1 [▼
          "Id" => array:32 [▼
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
        "mutation" => array:3 [▼
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
  "extrinsic_hash" => "a039eb0199b9fc28c46109d0056a41bdbddbe7ed72f5dad42d31a52542d55c74"
]
*/
