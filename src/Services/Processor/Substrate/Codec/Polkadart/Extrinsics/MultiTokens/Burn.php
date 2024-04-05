<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Burn extends Extrinsic implements PolkadartExtrinsic
{
}

/*
array:5 [▼
  "extrinsic_length" => 111
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "889f31681c922330a80fd6c48b7d9d5a320e97c2adc76ee6b19c74a90e1d6c1a6eb5381e2ea7c1f9d6b11ee97789b2e0bd64f71ba19fba112d132a06ed0f308e"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal84" => 1
      ]
      "CheckNonce" => 45
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "burn" => array:2 [▼
        "collection_id" => "13158"
        "params" => array:4 [▼
          "token_id" => "1"
          "amount" => "1"
          "keep_alive" => false
          "remove_token_storage" => false
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x099d60de818f02c53216188dc795b0af86925b7fd48e90e2387b6aac4c8eb758"
]
*/
