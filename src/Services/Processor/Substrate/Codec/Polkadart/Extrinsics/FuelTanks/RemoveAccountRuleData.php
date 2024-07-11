<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAccountRuleData extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
    "hash" => "7aa5ad1a1ff1bd8447729e3d0c93c633a2db11bf9e6de36b3b8a367f709674ad"
    "extrinsic_length" => 178
    "version" => 4
    "signature" => array:3 [▼
      "address" => array:1 [▼
        "Id" => array:32 [▶]
      ]
      "signature" => array:1 [▶]
      "signedExtensions" => array:4 [▼
        "era" => array:2 [▼
          "period" => 32
          "phase" => 6
        ]
        "nonce" => 175
        "tip" => "0"
        "metadata_hash" => "Disabled"
      ]
    ]
    "calls" => array:1 [▼
      "FuelTanks" => array:1 [▼
        "remove_account_rule_data" => array:4 [▼
          "tank_id" => array:1 [▼
            "Id" => array:32 [▼
              0 => 123
              1 => 78
              2 => 1
              3 => 154
              4 => 122
              5 => 43
              6 => 8
              7 => 66
              8 => 142
              9 => 208
              10 => 141
              11 => 164
              12 => 0
              13 => 53
              14 => 1
              15 => 180
              16 => 114
              17 => 189
              18 => 231
              19 => 237
              20 => 254
              21 => 243
              22 => 194
              23 => 222
              24 => 207
              25 => 97
              26 => 139
              27 => 77
              28 => 181
              29 => 180
              30 => 22
              31 => 179
            ]
          ]
          "user_id" => array:1 [▼
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
          "rule_set_id" => 0
          "rule_kind" => "UserFuelBudget"
        ]
      ]
    ]
    "extrinsic_hash" => "7aa5ad1a1ff1bd8447729e3d0c93c633a2db11bf9e6de36b3b8a367f709674ad"
  ]
*/
