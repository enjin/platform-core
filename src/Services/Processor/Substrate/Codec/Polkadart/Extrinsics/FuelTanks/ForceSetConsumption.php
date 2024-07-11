<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class ForceSetConsumption extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "e3112c0c75cc5fbffd25ad8b2ffccca87a965551479918efcd425cb397567215"
  "extrinsic_length" => 154
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▶]
    "signature" => array:1 [▼
      "Sr25519" => array:64 [▶]
    ]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 12
      ]
      "nonce" => 162
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "force_set_consumption" => array:4 [▼
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
        "user_id" => null
        "rule_set_id" => 4
        "consumption" => array:2 [▼
          "total_consumed" => "1000000000"
          "last_reset_block" => 38000
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "e3112c0c75cc5fbffd25ad8b2ffccca87a965551479918efcd425cb397567215"
]
*/
