<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Mint extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "b7338dec9ae05eccb59593168b566a557538c4ca21b7b33c7b3e583be9939aef"
  "extrinsic_length" => 210
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
        "phase" => 24
      ]
      "nonce" => 97
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "mint" => array:3 [▼
       "recipient" => array:1 [▼
        "Id" => array:32 [▼
          0 => 198
          1 => 96
          2 => 254
          3 => 244
          4 => 192
          5 => 146
          6 => 110
          7 => 56
          8 => 40
          9 => 57
          10 => 210
          11 => 12
          12 => 174
          13 => 230
          14 => 212
          15 => 227
          16 => 173
          17 => 180
          18 => 210
          19 => 126
          20 => 198
          21 => 107
          22 => 34
          23 => 62
          24 => 214
          25 => 69
          26 => 104
          27 => 69
          28 => 25
          29 => 110
          30 => 62
          31 => 121
        ]
      ]
      "collection_id" => "91971"
      "params" => array:1 [▼
        "CreateToken" => array:12 [▼
          "token_id" => "2"
          "initial_supply" => "1"
          "account_deposit_count" => 1
          "cap" => array:1 [▼
            "Supply" => "1"
          ]
          "behavior" => array:1 [▼
            "IsCurrency" => null
          ]
          "listing_forbidden" => true
          "freeze_state" => "Temporary"
          "attributes" => array:1 [▼
            0 => array:2 [▼
              "key" => array:4 [▶]
              "value" => array:4 [▶]
            ]
          ]
          "infusion" => "1000"
          "anyone_can_infuse" => true
          "metadata" => array:3 [▼
            "name" => array:10 [▼
              0 => 84
              1 => 101
              2 => 115
              3 => 116
              4 => 32
              5 => 84
              6 => 111
              7 => 107
              8 => 101
              9 => 110
            ]
            "symbol" => array:3 [▼
              0 => 84
              1 => 83
              2 => 84
            ]
            "decimal_count" => 8
          ]
          "privileged_params" => null
        ]
      ]
    ]
    ]
  ]
  "extrinsic_hash" => "b7338dec9ae05eccb59593168b566a557538c4ca21b7b33c7b3e583be9939aef"
]
*/
