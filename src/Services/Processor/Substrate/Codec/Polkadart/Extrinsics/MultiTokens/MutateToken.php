<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class MutateToken extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "367b29336ceb008584b139168e01a46af1fd4956ff9ccae430185ecbe370233b"
  "extrinsic_length" => 161
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
        "phase" => 21
      ]
      "nonce" => 102
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "mutate_token" => array:3 [▼
        "collection_id" => "77161"
        "token_id" => "1"
        "mutation" => array:4 [▼
          "behavior" => array:1 [▼
            "SomeMutation" => array:1 [▼
              "HasRoyalty" => array:2 [▼
                "beneficiary" => array:32 [▼
                  0 => 212
                  1 => 53
                  2 => 147
                  3 => 199
                  4 => 21
                  5 => 253
                  6 => 211
                  7 => 28
                  8 => 97
                  9 => 20
                  10 => 26
                  11 => 189
                  12 => 4
                  13 => 169
                  14 => 159
                  15 => 214
                  16 => 130
                  17 => 44
                  18 => 133
                  19 => 88
                  20 => 133
                  21 => 76
                  22 => 205
                  23 => 227
                  24 => 154
                  25 => 86
                  26 => 132
                  27 => 231
                  28 => 165
                  29 => 109
                  30 => 162
                  31 => 125
                ]
                "percentage" => 10000000
              ]
            ]
          ]
          "listing_forbidden" => array:1 [▼
            "SomeMutation" => false
          ]
          "anyone_can_infuse" => array:1 [▼
            "SomeMutation" => true
          ]
          "name" => array:1 [▼
            "SomeMutation" => array:4 [▼
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
  "extrinsic_hash" => "367b29336ceb008584b139168e01a46af1fd4956ff9ccae430185ecbe370233b"
]
*/
