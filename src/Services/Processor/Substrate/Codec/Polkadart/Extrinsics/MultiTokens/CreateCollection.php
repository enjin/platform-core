<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CreateCollection extends Extrinsic implements PolkadartExtrinsic {}

/*
array:5 [▼
  "extrinsic_length" => 184
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "fc8f2dedc041a86c937c9b46adfd58d967beb2fe50f994ac2286eaf277a5720f9796510b992c5124fc0b11bcd76e084622e98989a1f674212f2ce2d352fa1a88"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal196" => 1
      ]
      "CheckNonce" => 166
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "create_collection" => array:1 [▼
        "descriptor" => array:3 [▼
          "policy" => array:2 [▼
            "mint" => array:3 [▼
              "max_token_count" => array:1 [▼
                "Some" => "100000"
              ]
              "max_token_supply" => array:1 [▼
                "Some" => "5555555555"
              ]
              "force_single_mint" => false
            ]
            "market" => array:1 [▼
              "royalty" => array:1 [▼
                "Some" => array:2 [▼
                  "beneficiary" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
                  "percentage" => 10000000
                ]
              ]
            ]
          ]
          "explicit_royalty_currencies" => array:1 [▼
            0 => array:2 [▼
              "collection_id" => "0"
              "token_id" => "0"
            ]
          ]
          "attributes" => array:1 [▼
            0 => array:2 [▼
              "key" => array:4 [▼
                0 => 110
                1 => 97
                2 => 109
                3 => 101
              ]
              "value" => array:4 [▼
                0 => 68
                1 => 101
                2 => 109
                3 => 111
              ]
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0xeed170dbb4b7704c42242ae01da5712e569e2a7c4965ce3424725ef4b67b290e"
]
*/
