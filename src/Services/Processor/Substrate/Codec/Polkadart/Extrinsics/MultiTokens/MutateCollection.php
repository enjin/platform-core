<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class MutateCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "b8aa2e4e0f1113ea656dc83afc147f7b26e115bf0ae69d46390cf1f69ea2af8a"
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
        "phase" => 31
      ]
      "nonce" => 98
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "mutate_collection" => array:2 [▼
        "collection_id" => "77161"
        "mutation" => array:3 [▼
          "owner" => array:32 [▼
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
          "royalty" => array:1 [▼
            "SomeMutation" => array:2 [▼
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
              "percentage" => 10000000
            ]
          ]
          "explicit_royalty_currencies" => array:1 [▼
            0 => array:2 [▼
              "collection_id" => "0"
              "token_id" => "0"
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "b8aa2e4e0f1113ea656dc83afc147f7b26e115bf0ae69d46390cf1f69ea2af8a"
]
*/
