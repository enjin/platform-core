<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CreateFuelTank extends Extrinsic implements PolkadartExtrinsic {}

/* Example 1
[▼
  "hash" => "eb2d724b655a0a8cb8c43f5d11cda3bcaf008f94c6db1417d0e6a70efe1aa015"
  "extrinsic_length" => 200
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▼
      "Sr25519" => array:64 [▶]
    ]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▶]
      "nonce" => 124
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "create_fuel_tank" => array:1 [▼
        "descriptor" => array:5 [▼
          "name" => array:13 [▼
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
          "user_account_management" => true
          "rule_sets" => array:1 [▼
            0 => array:2 [▼
              0 => 0
              1 => array:2 [▼
                "rules" => array:1 [▼
                  0 => array:1 [▼
                    "WhitelistedCallers" => array:1 [▼
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
                    ]
                  ]
                ]
                "require_account" => true
              ]
            ]
          ]
          "coverage_policy" => "FeesAndDeposit"
          "account_rules" => array:1 [▼
            0 => array:1 [▼
              "WhitelistedCallers" => array:1 [▼
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
              ]
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "eb2d724b655a0a8cb8c43f5d11cda3bcaf008f94c6db1417d0e6a70efe1aa015"
]
*/
