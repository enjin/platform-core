<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class DestroyFuelTank extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
    "hash" => "892d4ee5e965a728ea4a7b7c553d6ecb3d43af7a1ab730093ec64f0f23d15a6c"
    "extrinsic_length" => 140
    "version" => 4
    "signature" => array:3 [▼
      "address" => array:1 [▼
        "Id" => array:32 [▶]
      ]
      "signature" => array:1 [▶]
      "signedExtensions" => array:4 [▼
        "era" => array:2 [▼
          "period" => 32
          "phase" => 30
        ]
        "nonce" => 142
        "tip" => "0"
        "metadata_hash" => "Disabled"
      ]
    ]
    "calls" => array:1 [▼
      "FuelTanks" => array:1 [▼
        "destroy_fuel_tank" => array:1 [▼
          "tank_id" => array:1 [▼
            "Id" => array:32 [▼
              0 => 2
              1 => 237
              2 => 43
              3 => 57
              4 => 28
              5 => 39
              6 => 85
              7 => 106
              8 => 76
              9 => 108
              10 => 189
              11 => 241
              12 => 198
              13 => 103
              14 => 34
              15 => 131
              16 => 245
              17 => 116
              18 => 175
              19 => 192
              20 => 108
              21 => 155
              22 => 70
              23 => 93
              24 => 205
              25 => 37
              26 => 28
              27 => 79
              28 => 22
              29 => 58
              30 => 166
              31 => 36
            ]
          ]
        ]
      ]
    ]
    "extrinsic_hash" => "892d4ee5e965a728ea4a7b7c553d6ecb3d43af7a1ab730093ec64f0f23d15a6c"
  ]
*/
