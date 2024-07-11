<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class ApproveToken extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "8a4cfa426c18753cf2b9d3275a04119f70df26f9cefea12cf187e0a533c10806"
  "extrinsic_length" => 150
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▶]
    "signature" => array:1 [▼
      "Sr25519" => array:64 [▶]
    ]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 7
      ]
      "nonce" => 0
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "approve_token" => array:6 [▼
        "collection_id" => "77161"
        "token_id" => "1"
        "operator" => array:32 [▼
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
        "amount" => "1"
        "expiration" => 100000
        "current_amount" => "0"
      ]
    ]
  ]
  "extrinsic_hash" => "8a4cfa426c18753cf2b9d3275a04119f70df26f9cefea12cf187e0a533c10806"
]
*/
