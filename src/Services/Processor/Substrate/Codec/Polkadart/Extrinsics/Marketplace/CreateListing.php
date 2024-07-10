<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CreateListing extends Extrinsic implements PolkadartExtrinsic {}

/*
[▼
  "hash" => "0933aae447636e157545ebacbb8547dbf504d4db5af4520b5590e41a988e9573"
  "extrinsic_length" => 130
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
        "phase" => 13
      ]
      "nonce" => 116
      "tip" => "0"
      "metadata_hash" => "Disabled"
    ]
  ]
  "calls" => array:1 [▼
    "Marketplace" => array:1 [▼
      "create_listing" => array:7 [▼
        "make_asset_id" => array:2 [▼
          "collection_id" => "77162"
          "token_id" => "1"
        ]
        "take_asset_id" => array:2 [▼
          "collection_id" => "0"
          "token_id" => "0"
        ]
        "amount" => "1"
        "price" => "100000"
        "salt" => []
        "listing_data" => array:1 [▼
          "Auction" => array:2 [▼
            "start_block" => 32180
            "end_block" => 32200
          ]
        ]
        "depositor" => null
      ]
    ]
  ]
  "extrinsic_hash" => "0933aae447636e157545ebacbb8547dbf504d4db5af4520b5590e41a988e9573"
]
*/
