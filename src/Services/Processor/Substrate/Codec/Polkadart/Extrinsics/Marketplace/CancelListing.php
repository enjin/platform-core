<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CancelListing extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "5f46ce461a0989fbec512f05e9b9576da4887b54d72a7356f69de537f5f7ed12"
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
        "phase" => 24
      ]
      "nonce" => 115
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "Marketplace" => array:1 [▼
      "cancel_listing" => array:1 [▼
        "listing_id" => array:32 [▼
          0 => 149
          1 => 73
          2 => 178
          3 => 222
          4 => 67
          5 => 104
          6 => 222
          7 => 175
          8 => 149
          9 => 83
          10 => 84
          11 => 31
          12 => 134
          13 => 78
          14 => 114
          15 => 8
          16 => 129
          17 => 106
          18 => 15
          19 => 118
          20 => 41
          21 => 217
          22 => 140
          23 => 9
          24 => 96
          25 => 137
          26 => 32
          27 => 181
          28 => 230
          29 => 57
          30 => 191
          31 => 199
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "5f46ce461a0989fbec512f05e9b9576da4887b54d72a7356f69de537f5f7ed12"
]
*/
