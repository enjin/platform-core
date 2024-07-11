<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class BatchTransfer extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "dd310306e5d8f96de1177f0aa11d3981d7e9c0944821431246914b70a52ad841"
  "extrinsic_length" => 184
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
        "phase" => 18
      ]
      "nonce" => 121
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "batch_transfer" => array:2 [▼
        "collection_id" => "77162"
        "recipients" => array:2 [▼
          0 => array:2 [▼
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
            "params" => array:1 [▼
              "Simple" => array:3 [▼
                "token_id" => "1"
                "amount" => "1"
                "depositor" => null
              ]
            ]
          ]
          1 => array:2 [▼
            "account_id" => array:32 [▼
              0 => 144
              1 => 181
              2 => 171
              3 => 32
              4 => 92
              5 => 105
              6 => 116
              7 => 201
              8 => 234
              9 => 132
              10 => 27
              11 => 230
              12 => 136
              13 => 134
              14 => 70
              15 => 51
              16 => 220
              17 => 156
              18 => 168
              19 => 163
              20 => 87
              21 => 132
              22 => 62
              23 => 234
              24 => 207
              25 => 35
              26 => 20
              27 => 100
              28 => 153
              29 => 101
              30 => 254
              31 => 34
            ]
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
    ]
  ]
  "extrinsic_hash" => "dd310306e5d8f96de1177f0aa11d3981d7e9c0944821431246914b70a52ad841"
]
*/
