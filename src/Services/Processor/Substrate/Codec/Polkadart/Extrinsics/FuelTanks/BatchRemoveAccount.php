<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class BatchRemoveAccount extends Extrinsic implements PolkadartExtrinsic {}

/*
[
    {
        "extrinsic_length": 206,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "72d800d429b02167668a7b82fbf6eb6054a86b57d442018eb9eb1ac5fc965d0b9dbce2feaebe4a6338bfb705bd42affde0d3ceae24ece5838e6d9d531bcc1080"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal20": 1
                },
                "CheckNonce": 12018,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "batch_remove_account": {
                    "tank_id": {
                        "Id": "f8d962353abb6d609d0a7c566d6f4a94271f7ddd68d8f1a1b9c2baf7ae173da8"
                    },
                    "user_ids": [
                        {
                            "Id": "d262026b9f63cff14e06d54e85485e2c4d6458de2cf4858b4ce365a519fa3e51"
                        },
                        {
                            "Id": "4e0ff7b256ec986362ef446f67cf28d851496ac8d74d3777ed75be8548621952"
                        }
                    ]
                }
            }
        },
        "extrinsic_hash": "0xbc9a7b281d046db1dc42494bae0bf8c16ba98eb616e9b5ca14efe603df6509d0"
    }
]
*/
