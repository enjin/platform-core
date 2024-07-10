<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveRuleSet extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "96d4cb72d81bd9ea2968a882f46bb67818aa5e2aa74c5c8aae965cfcd32e6831"
  "extrinsic_length" => 144
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
        "phase" => 29
      ]
      "nonce" => 135
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "remove_rule_set" => array:2 [▼
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
        "rule_set_id" => 1
      ]
    ]
  ]
  "extrinsic_hash" => "96d4cb72d81bd9ea2968a882f46bb67818aa5e2aa74c5c8aae965cfcd32e6831"
]
*/
