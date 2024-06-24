<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAccount extends Extrinsic implements PolkadartExtrinsic {}

/*
[
    {
        "extrinsic_length": 172,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "9a04bb98afdaf308d35b0292457616b5839dec5b65b2b386c453ad72d47d2f7f5f5ba4d2f1fedde37cabdcee2d8333c8b50e2f47320bc8efb0e573c5cc06128c"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal4": 0
                },
                "CheckNonce": 12096,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "remove_account": {
                    "tank_id": {
                        "Id": "3bcde4366eeb3372ae358fc725842e62d5b6ee9e2feb606ebfc36c27a3b23925"
                    },
                    "user_id": {
                        "Id": "d262026b9f63cff14e06d54e85485e2c4d6458de2cf4858b4ce365a519fa3e51"
                    }
                }
            }
        },
        "extrinsic_hash": "0x980b49f65b2ad4735de79e24081fa8191797e87b6ef593196a1aea0093587223"
    }
]
*/
