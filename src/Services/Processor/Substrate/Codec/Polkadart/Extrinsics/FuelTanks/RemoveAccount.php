<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAccount extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "1857e7a63142b609c12b81e1a857d7a637f5fcb5400cb120c3a387ee8df00616"
  "extrinsic_length" => 173
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
        "phase" => 19
      ]
      "nonce" => 152
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "remove_account" => array:2 [▼
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
        "user_id" => array:1 [▼
          "Id" => array:32 [▼
            0 => 28
            1 => 189
            2 => 45
            3 => 67
            4 => 83
            5 => 10
            6 => 68
            7 => 112
            8 => 90
            9 => 208
            10 => 136
            11 => 175
            12 => 49
            13 => 62
            14 => 24
            15 => 248
            16 => 11
            17 => 83
            18 => 239
            19 => 22
            20 => 179
            21 => 97
            22 => 119
            23 => 205
            24 => 75
            25 => 119
            26 => 184
            27 => 70
            28 => 242
            29 => 165
            30 => 240
            31 => 124
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "1857e7a63142b609c12b81e1a857d7a637f5fcb5400cb120c3a387ee8df00616"
]
*/
