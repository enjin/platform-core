<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class FillListing extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
    "hash" => "3fbcf42fea7619c367468ddec8ceb34603d6d640799fb2332687cb62b4a8d46e"
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
          "phase" => 4
        ]
        "nonce" => 10
        "tip" => "0"
        "metadata_hash" => "Disabled"
      ]
    ]
    "calls" => array:1 [▼
      "Marketplace" => array:1 [▼
        "fill_listing" => array:2 [▼
          "listing_id" => array:32 [▼
            0 => 22
            1 => 179
            2 => 176
            3 => 36
            4 => 199
            5 => 139
            6 => 161
            7 => 47
            8 => 160
            9 => 94
            10 => 211
            11 => 61
            12 => 175
            13 => 33
            14 => 23
            15 => 77
            16 => 241
            17 => 246
            18 => 115
            19 => 166
            20 => 255
            21 => 151
            22 => 51
            23 => 18
            24 => 81
            25 => 20
            26 => 80
            27 => 76
            28 => 77
            29 => 6
            30 => 22
            31 => 212
          ]
          "amount" => "1"
        ]
      ]
    ]
    "extrinsic_hash" => "3fbcf42fea7619c367468ddec8ceb34603d6d640799fb2332687cb62b4a8d46e"
  ]
]
*/
