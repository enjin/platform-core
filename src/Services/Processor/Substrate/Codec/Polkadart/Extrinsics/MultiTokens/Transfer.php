<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Transfer extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 145
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "1c5e1da29eb5e8cff9eb3c37e65deb5b83328c85e014c9bcb2d8f6a3f0dc38441307e0573466fa703b737e4c24ac49a674e1d6cecb32f3021cd84bc6bff1f383"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal244" => 0
      ]
      "CheckNonce" => 174
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "transfer" => array:3 [▼
        "recipient" => array:1 [▼
          "Id" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
        ]
        "collection_id" => "13158"
        "params" => array:1 [▼
          "Simple" => array:3 [▼
            "token_id" => "1"
            "amount" => "1"
            "keep_alive" => false
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x6b35502360dcbcbc70ec52275deec29961274b6c23c77d54f1e6f7f53c442e36"
]
*/
