<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class ApproveToken extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 148
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "c842c3b1eca0b0376933d6cc390caae5d3992a6cb7ab7fc032728b66da7e912752d8188bd8df594b63469fe69653e531fcdf37f0e676b083b101c09fef23ea8e"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal212" => 0
      ]
      "CheckNonce" => 173
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "approve_token" => array:6 [▼
        "collection_id" => "13158"
        "token_id" => "1"
        "operator" => "90b5ab205c6974c9ea841be688864633dc9ca8a357843eeacf2314649965fe22"
        "amount" => "1"
        "expiration" => array:1 [▼
          "Some" => 500000
        ]
        "current_amount" => "0"
      ]
    ]
  ]
  "extrinsic_hash" => "0xfbdf788d8c120486e09794af4c6fe6c475b908269cda15f288cb04e09b49dea9"
]
*/
