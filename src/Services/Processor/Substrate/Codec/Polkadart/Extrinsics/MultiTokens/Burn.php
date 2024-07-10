<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Burn extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "b189121029f729763a634bc7bc4afc9277bd925d5ff78e4c4f6b0812c65c39e8"
  "extrinsic_length" => 114
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▶]
    "signature" => array:1 [▼
      "Sr25519" => array:64 [▶]
    ]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 27
      ]
      "nonce" => 109
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "burn" => array:2 [▼
        "collection_id" => "77161"
        "params" => array:3 [▼
          "token_id" => "1"
          "amount" => "1"
          "remove_token_storage" => false
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "b189121029f729763a634bc7bc4afc9277bd925d5ff78e4c4f6b0812c65c39e8"
]
*/
