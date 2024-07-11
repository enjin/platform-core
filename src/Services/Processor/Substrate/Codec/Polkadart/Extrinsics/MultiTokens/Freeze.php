<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Freeze extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "1d65b1d7f4e701b423f7c8a051d5cb28e57f4ed9ef0c36d87bacc85ef9d7bfdc"
  "extrinsic_length" => 145
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
        "phase" => 13
      ]
      "nonce" => 100
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "freeze" => array:1 [▼
        "info" => array:2 [▼
          "collection_id" => "77161"
          "freeze_type" => array:1 [▼
            "TokenAccount" => array:2 [▼
              "token_id" => "1"
              "account_id" => array:32 [▼
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
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "1d65b1d7f4e701b423f7c8a051d5cb28e57f4ed9ef0c36d87bacc85ef9d7bfdc"
]
*/
