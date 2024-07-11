<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class BatchSetAttribute extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "d5ffead03b7affd822d5fbeda25f38e376382fb52ab7f1a5c71e2e4e6270c032"
  "extrinsic_length" => 156
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
        "phase" => 1
      ]
      "nonce" => 120
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "batch_set_attribute" => array:4 [▼
        "collection_id" => "77162"
        "token_id" => null
        "attributes" => array:2 [▼
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
          1 => array:2 [▼
            "key" => array:3 [▼
              0 => 117
              1 => 114
              2 => 105
            ]
            "value" => array:27 [▼
              0 => 104
              1 => 116
              2 => 116
              3 => 112
              4 => 115
              5 => 58
              6 => 47
              7 => 47
              8 => 116
              9 => 101
              10 => 115
              11 => 116
              12 => 46
              13 => 99
              14 => 111
              15 => 109
              16 => 47
              17 => 55
              18 => 55
              19 => 49
              20 => 54
              21 => 50
              22 => 46
              23 => 106
              24 => 115
              25 => 111
              26 => 110
            ]
          ]
        ]
        "depositor" => null
      ]
    ]
  ]
  "extrinsic_hash" => "d5ffead03b7affd822d5fbeda25f38e376382fb52ab7f1a5c71e2e4e6270c032"
]
*/
