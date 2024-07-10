<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class DestroyCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "14611d66e014fa232ce3371ae6d129aa8dfb39a0f529f149bfe363a7837820ed"
  "extrinsic_length" => 111
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
        "phase" => 3
      ]
      "nonce" => 111
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "destroy_collection" => array:1 [▼
        "collection_id" => "77161"
      ]
    ]
  ]
  "extrinsic_hash" => "14611d66e014fa232ce3371ae6d129aa8dfb39a0f529f149bfe363a7837820ed"
]
*/
