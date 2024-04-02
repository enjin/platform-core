<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAttribute extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 130
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "a6f2f05f24fefd1d22e1480e12cdcc96a9afb4084729cbca853039418733e601693ed9b332ca75214bb4168823d0832d4bc847fc9c7abae531be11a98f259e8f"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▶]
      "CheckNonce" => 170
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "remove_attribute" => array:3 [▼
        "collection_id" => "13159"
        "token_id" => array:1 [▼
          "Some" => "1"
        ]
        "key" => array:4 [▼
          0 => 110
          1 => 97
          2 => 109
          3 => 101
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x048f54cdda10c8f092a73536c02992c673f400e4cc802a8cacdb1e6260b65d7d"
]
*/
