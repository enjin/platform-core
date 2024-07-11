<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class UnapproveToken extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "616276e06cc2990327b23f9524dae1bcb961544d3764ed29c2e7f77a412f5d7e"
  "extrinsic_length" => 143
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▶]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 7
      ]
      "nonce" => 1
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "unapprove_token" => array:3 [▼
        "collection_id" => "77161"
        "token_id" => "1"
        "operator" => array:32 [▶]
      ]
    ]
  ]
  "extrinsic_hash" => "616276e06cc2990327b23f9524dae1bcb961544d3764ed29c2e7f77a412f5d7e"
]
*/
