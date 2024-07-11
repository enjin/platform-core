<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Transfer extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "d51323b1f5a1c5dbc04df5034bacc81365c3f8fef67b76fee0aba5f2ad5bec39"
  "extrinsic_length" => 147
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
        "phase" => 23
      ]
      "nonce" => 5
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "transfer" => array:3 [▼
        "recipient" => array:1 [▼
          "Id" => array:32 [▼
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
        ]
        "collection_id" => "77161"
        "params" => array:1 [▼
          "Simple" => array:3 [▼
            "token_id" => "1"
            "amount" => "1"
            "depositor" => null
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "d51323b1f5a1c5dbc04df5034bacc81365c3f8fef67b76fee0aba5f2ad5bec39"
]
*/
