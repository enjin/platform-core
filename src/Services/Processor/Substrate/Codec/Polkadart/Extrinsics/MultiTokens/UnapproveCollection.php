<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class UnapproveCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "6b22e95ba0f8e7b613de2028e3dd41d548e1c5d23aec3b9c9ce9cd6f859bb839"
  "extrinsic_length" => 142
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▶]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 11
      ]
      "nonce" => 2
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "unapprove_collection" => array:2 [▼
        "collection_id" => "77161"
        "operator" => array:32 [▶]
      ]
    ]
  ]
  "extrinsic_hash" => "6b22e95ba0f8e7b613de2028e3dd41d548e1c5d23aec3b9c9ce9cd6f859bb839"
]
*/
