<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAttribute extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "ca36be205334e68722f8be1a2b05dfa4581cce5ee614d9fbf417560f7df0a929"
  "extrinsic_length" => 133
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
        "phase" => 29
      ]
      "nonce" => 104
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "remove_attribute" => array:3 [▼
        "collection_id" => "77161"
        "token_id" => "1"
        "key" => array:4 [▼
          0 => 110
          1 => 97
          2 => 109
          3 => 101
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "ca36be205334e68722f8be1a2b05dfa4581cce5ee614d9fbf417560f7df0a929"
]
*/
