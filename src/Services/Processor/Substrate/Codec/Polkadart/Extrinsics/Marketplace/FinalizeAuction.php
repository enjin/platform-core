<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class FinalizeAuction extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
    "hash" => "605d1e0453c1bb855608e276906d2af331a0d3da75b9243e41fe55769619b5a4"
    "extrinsic_length" => 139
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
          "phase" => 19
        ]
        "nonce" => 119
        "tip" => "0"
        "metadata_hash" => "Disabled"
      ]
    ]
    "calls" => array:1 [▼
      "Marketplace" => array:1 [▼
        "finalize_auction" => array:1 [▼
          "listing_id" => array:32 [▼
            0 => 57
            1 => 116
            2 => 137
            3 => 159
            4 => 32
            5 => 135
            6 => 226
            7 => 53
            8 => 97
            9 => 171
            10 => 138
            11 => 227
            12 => 157
            13 => 252
            14 => 214
            15 => 65
            16 => 207
            17 => 67
            18 => 210
            19 => 101
            20 => 100
            21 => 134
            22 => 230
            23 => 149
            24 => 153
            25 => 36
            26 => 208
            27 => 235
            28 => 193
            29 => 151
            30 => 173
            31 => 133
          ]
        ]
      ]
    ]
    "extrinsic_hash" => "605d1e0453c1bb855608e276906d2af331a0d3da75b9243e41fe55769619b5a4"
  ]
*/
