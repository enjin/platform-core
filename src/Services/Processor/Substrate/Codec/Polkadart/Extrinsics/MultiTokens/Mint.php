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
          "Id" => array:32 [▶]
        ]
        "collection_id" => "77161"
        "params" => array:1 [▼
          "CreateToken" => array:12 [▼
            "token_id" => "1"
            "initial_supply" => "20"
            "account_deposit_count" => 0
            "cap" => null
            "behavior" => array:1 [▼
              "HasRoyalty" => array:2 [▼
                "beneficiary" => array:32 [▼
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
                "percentage" => 100000000
              ]
            ]
            "listing_forbidden" => true
            "freeze_state" => "Temporary"
            "attributes" => array:1 [▶]
            "infusion" => "100000000"
            "anyone_can_infuse" => false
            "metadata" => array:3 [▼
              "name" => []
              "symbol" => []
              "decimal_count" => 0
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
