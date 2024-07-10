<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class PlaceBid extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "8a001b8578aa6b2e986041c3da1348a5b0b92a0c4eaf3f62369ff566bd3ccccd"
  "extrinsic_length" => 144
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
      "nonce" => 0
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "Marketplace" => array:1 [▼
      "place_bid" => array:2 [▼
        "listing_id" => array:32 [▼
          0 => 202
          1 => 91
          2 => 24
          3 => 230
          4 => 83
          5 => 36
          6 => 183
          7 => 253
          8 => 149
          9 => 87
          10 => 173
          11 => 131
          12 => 17
          13 => 119
          14 => 184
          15 => 91
          16 => 147
          17 => 170
          18 => 177
          19 => 158
          20 => 253
          21 => 17
          22 => 182
          23 => 148
          24 => 159
          25 => 237
          26 => 57
          27 => 139
          28 => 252
          29 => 221
          30 => 67
          31 => 31
        ]
        "price" => "10000000000"
      ]
    ]
  ]
  "extrinsic_hash" => "8a001b8578aa6b2e986041c3da1348a5b0b92a0c4eaf3f62369ff566bd3ccccd"
]
*/
