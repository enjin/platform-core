<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class BatchMint extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "ac7ddba4e2948fd6af1990fa170979e8204785f4b49b41d5beeaf2ab75fe2ea1"
  "extrinsic_length" => 216
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
        "phase" => 4
      ]
      "nonce" => 122
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "batch_mint" => array:2 [▼
        "collection_id" => "77162"
        "recipients" => array:2 [▼
          0 => array:2 [▼
            "account_id" => array:32 [▼
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
            "params" => array:1 [▼
              "CreateToken" => array:12 [▼
                "token_id" => "2"
                "initial_supply" => "10"
                "account_deposit_count" => 0
                "cap" => null
                "behavior" => null
                "listing_forbidden" => false
                "freeze_state" => null
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
                "infusion" => "0"
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
          1 => array:2 [▼
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
              "CreateToken" => array:12 [▼
                "token_id" => "3"
                "initial_supply" => "1"
                "account_deposit_count" => 0
                "cap" => null
                "behavior" => null
                "listing_forbidden" => false
                "freeze_state" => null
                "attributes" => []
                "infusion" => "0"
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
    ]
  ]
  "extrinsic_hash" => "ac7ddba4e2948fd6af1990fa170979e8204785f4b49b41d5beeaf2ab75fe2ea1"
]
*/
