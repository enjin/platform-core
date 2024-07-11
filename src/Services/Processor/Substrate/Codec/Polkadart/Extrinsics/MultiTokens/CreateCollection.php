<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CreateCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "f59154f87941fc47cc56ff9b1d122b524d296ac992821d0cd80db75a88dcfc67"
  "extrinsic_length" => 186
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
      "nonce" => 92
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "create_collection" => array:1 [▼
        "descriptor" => array:4 [▼
          "policy" => array:5 [▼
            "mint" => array:3 [▼
              "max_token_count" => "1000000"
              "max_token_supply" => "50000"
              "force_collapsing_supply" => true
            ]
            "burn" => null
            "transfer" => null
            "attribute" => null
            "market" => array:2 [▼
              "beneficiary" => array:32 [▼
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
              "percentage" => 10000000
            ]
          ]
          "depositor" => null
          "explicit_royalty_currencies" => array:1 [▼
            0 => array:2 [▼
              "collection_id" => "0"
              "token_id" => "0"
            ]
          ]
          "attributes" => array:1 [▼
            0 => array:2 [▼
              "key" => array:4 [▼
                0 => 110
                1 => 97
                2 => 109
                3 => 101
              ]
              "value" => array:4 [▼
                0 => 84
                1 => 101
                2 => 115
                3 => 116
              ]
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "f59154f87941fc47cc56ff9b1d122b524d296ac992821d0cd80db75a88dcfc67"
]
*/
