<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Freeze extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 142
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "e481e2903c9ae573bf6de75eddd990039e968068ff71f838307e254a7376467e3ca4c5c0d309b66c0031118b7510b78f6bf40751527c45791de1914fca27fb85"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal4" => 1
      ]
      "CheckNonce" => 175
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "freeze" => array:1 [▼
        "info" => array:2 [▼
          "collection_id" => "13158"
          "freeze_type" => array:1 [▼
            "TokenAccount" => array:2 [▼
              "token_id" => "1"
              "account_id" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x2700c1191f799ff9442984d9be8595462ff03841ac3e6267078028c0dc26cfd9"
]
*/
