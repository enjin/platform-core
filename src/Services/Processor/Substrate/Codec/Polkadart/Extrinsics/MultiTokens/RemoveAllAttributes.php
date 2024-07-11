<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAllAttributes extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "de6910f0a7c322a074a65337ba3e07aeffef0e3bbe86bce84f85950e602140b4"
  "extrinsic_length" => 116
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
        "phase" => 0
      ]
      "nonce" => 105
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "remove_all_attributes" => array:3 [▼
        "collection_id" => "77161"
        "token_id" => null
        "attribute_count" => 1
      ]
    ]
  ]
  "extrinsic_hash" => "de6910f0a7c322a074a65337ba3e07aeffef0e3bbe86bce84f85950e602140b4"
]
*/
