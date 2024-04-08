<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class SetAttribute extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 141
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "bee511a4415d361f81e89296a1727592f79dea94596cb1aa31e7c198f1d89f53e01a5c44683a4d4e72b33f55ed277e665dcb1f1248fb03b095433a0d54e7778b"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal68" => 0
      ]
      "CheckNonce" => 168
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "set_attribute" => array:4 [▼
        "collection_id" => "13158"
        "token_id" => array:1 [▼
          "Some" => "1"
        ]
        "key" => array:4 [▼
          0 => 110
          1 => 97
          2 => 109
          3 => 101
        ]
        "value" => array:10 [▼
          0 => 68
          1 => 101
          2 => 109
          3 => 111
          4 => 32
          5 => 84
          6 => 111
          7 => 107
          8 => 101
          9 => 110
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x4cc565efa5251da82c9fa73ebeb33599508b18c207b17bccbb51786be2d7c097"
]
*/
