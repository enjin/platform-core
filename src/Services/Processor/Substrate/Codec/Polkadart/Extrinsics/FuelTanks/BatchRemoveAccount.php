<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class BatchRemoveAccount extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "29468969793b283f196f532acb39e629731b58fe8a4d8d7dc294cb2b378e927b"
  "extrinsic_length" => 174
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▶]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▶]
      "nonce" => 153
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "FuelTanks" => array:1 [▼
      "batch_remove_account" => array:2 [▼
        "tank_id" => array:1 [▼
          "Id" => array:32 [▼
            0 => 89
            1 => 184
            2 => 117
            3 => 198
            4 => 158
            5 => 220
            6 => 161
            7 => 224
            8 => 130
            9 => 188
            10 => 156
            11 => 88
            12 => 51
            13 => 69
            14 => 25
            15 => 88
            16 => 225
            17 => 190
            18 => 240
            19 => 218
            20 => 217
            21 => 220
            22 => 14
            23 => 215
            24 => 197
            25 => 225
            26 => 53
            27 => 227
            28 => 59
            29 => 159
            30 => 183
            31 => 137
          ]
        ]
        "user_ids" => array:1 [▼
          0 => array:1 [▼
            "Id" => array:32 [▼
              0 => 230
              1 => 89
              2 => 167
              3 => 161
              4 => 98
              5 => 140
              6 => 221
              7 => 147
              8 => 254
              9 => 188
              10 => 4
              11 => 164
              12 => 224
              13 => 100
              14 => 110
              15 => 162
              16 => 14
              17 => 159
              18 => 95
              19 => 12
              20 => 224
              21 => 151
              22 => 217
              23 => 160
              24 => 82
              25 => 144
              26 => 212
              27 => 169
              28 => 224
              29 => 84
              30 => 223
              31 => 78
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "29468969793b283f196f532acb39e629731b58fe8a4d8d7dc294cb2b378e927b"
]
*/
