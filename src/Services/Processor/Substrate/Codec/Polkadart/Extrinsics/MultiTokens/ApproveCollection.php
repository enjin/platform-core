<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class ApproveCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "37c7583491403b8b6039063c8cbcd001b9e0321b58a27a8513df5935dcc64032"
  "extrinsic_length" => 148
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
        "phase" => 9
      ]
      "nonce" => 93
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "approve_collection" => array:3 [▼
        "collection_id" => "77161"
        "operator" => array:32 [▼
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
        "expiration" => 60000
      ]
    ]
  ]
  "extrinsic_hash" => "37c7583491403b8b6039063c8cbcd001b9e0321b58a27a8513df5935dcc64032"
]
*/
