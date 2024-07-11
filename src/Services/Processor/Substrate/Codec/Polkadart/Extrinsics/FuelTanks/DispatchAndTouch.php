<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class DispatchAndTouch extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "462a1f0f49d5afd44f915443413869469f0e003e28a5f4fd83b0a83715acf027"
  "extrinsic_length" => 156
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
      "nonce" => 167
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "dispatch_and_touch" => array:4 [▼
        "tank_id" => array:1 [▼
          "Id" => array:32 [▼
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
        "rule_set_id" => 0
        "call" => array:1 [▼
          "MultiTokens" => array:1 [▼
            "create_collection" => array:1 [▼
              "descriptor" => array:4 [▼
                "policy" => array:5 [▶]
                "depositor" => null
                "explicit_royalty_currencies" => array:1 [▶]
                "attributes" => []
              ]
            ]
          ]
        ]
        "settings" => null
      ]
    ]
  ]
  "extrinsic_hash" => "462a1f0f49d5afd44f915443413869469f0e003e28a5f4fd83b0a83715acf027"
]
*/
