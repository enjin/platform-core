<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class ApproveCollection extends Extrinsic implements PolkadartExtrinsic
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
      "Sr25519" => "7433dba41aebafb4ae80d66eeb92c0ac1cda639066e3fd38466451a388a4b24f970b02d7f08a0e33ee7b5d973e58abad66de000fef138f95b5952e1d64b74a83"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal100" => 0
      ]
      "CheckNonce" => 169
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "approve_collection" => array:3 [▼
        "collection_id" => "13159"
        "operator" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
        "expiration" => array:1 [▼
          "Some" => 300000
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0xcaa235d4d357fd43ecc7c4af235132fced9b5b519374ed3bb1ebc6f72962ff6b"
]
*/
