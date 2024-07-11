<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class SetAttribute extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "a7493be889be3c7280a28123434af042642e075c3a58fa2d0f249a15825dc6f6"
  "extrinsic_length" => 147
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => array:32 [▶]
    ]
    "signature" => array:1 [▶]
    "signedExtensions" => array:4 [▼
      "era" => array:2 [▼
        "period" => 32
        "phase" => 25
      ]
      "nonce" => 103
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "set_attribute" => array:5 [▼
        "collection_id" => "77161"
        "token_id" => "1"
        "key" => array:4 [▼
          0 => 110
          1 => 97
          2 => 109
          3 => 101
        ]
        "value" => array:12 [▼
          0 => 65
          1 => 110
          2 => 111
          3 => 116
          4 => 104
          5 => 101
          6 => 114
          7 => 32
          8 => 84
          9 => 101
          10 => 115
          11 => 116
        ]
        "depositor" => null
      ]
    ]
  ]
  "extrinsic_hash" => "a7493be889be3c7280a28123434af042642e075c3a58fa2d0f249a15825dc6f6"
]
*/
